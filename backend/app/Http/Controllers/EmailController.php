<?php

namespace App\Http\Controllers;


use App\Models\EmailTemplate;
use App\Repositories\Contracts\ClientRepositoryInterface;
use App\Repositories\Contracts\ContactRepositoryInterface;
use App\Repositories\Contracts\EmailRepositoryInterface;
use Illuminate\Http\Request;
use App\Services\PHPMailerService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class EmailController extends Controller
{
    protected $mailerService;
    protected $client;
    protected  $contact;



    public function __construct(
        PHPMailerService $mailerService,
        ContactRepositoryInterface $contact,
        ClientRepositoryInterface $client
    ) {
        $this->mailerService = $mailerService;
        $this->contact = $contact;
        $this->client = $client;
    }
    public function returnTemplate(Request $request)
    {
        try {
            $clientId = $request->input('client_id');
            $contactId = $request->input('contact_id');
            $document = $request->input('document');
            $documentCode = $request->input('document_code');
            $templateId = $request->input('template');

            $client = $this->client->findById($clientId);
            $contact = $this->contact->getById($contactId);

            if (!$client || !$contact) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Client ou contact introuvable.',
                ], 404);
            }

            $template = EmailTemplate::findOrFail($templateId);

            $content = Blade::render($template->content, [
                'contact' => $contact,
                'client' => $client,
                'document' => $document,
            ]);

            return response()->json([
                'status' => 'success',
                'to' => $contact->email ?? '',
                'subject' => 'Notification concernant votre document',
                'content' => $content,
                'client_id' => $clientId,
                'contact_id' => $contactId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur lors du rendu du template :', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Une erreur inattendue est survenue.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function getAll(Request $request, EmailRepositoryInterface $repository)
    {
        try {
            $filters = [
                'search' => $request->input('search'),
            ];

            $perPage = $request->input('per_page', 10);

            $query = $repository->getEmails()->with(['client:id,legal_name,address,email', 'contact:id,first_name,full_name,email']);

            if (!empty($filters['search'])) {
                $searchQuery = $filters['search'];
                $query->whereHas('client', function ($clientQuery) use ($searchQuery) {
                    $clientQuery->where('legal_name', 'LIKE', "%$searchQuery%");
                });
            }

            $query->orderBy('created_at', 'desc');
            $emails = $query->paginate($perPage);

            $emails->getCollection()->transform(function ($email) {
                $templateData = [
                    'contact' => $email->contact,
                    'client' => $email->client,
                    'document' => null,
                    'documentCode' => null,
                    'to' => $email->contact->email ?? null,
                    'subject' => $email->subject,
                    'messageBody' => $email->content,
                    'date' => $email->created_at->format('d/m/Y'),
                    'recipientName' => $email->contact->first_name ?? null,
                    'recipientEmail' => $email->contact->email ?? null,
                    'companyName' => $email->client->legal_name ?? config('app.name'),
                    'companyAddress' => $email->client->address ?? null,
                    'companyEmail' => $email->client->email ?? config('mail.from.address'),
                    'companyPhone' => $email->client->phone ?? null,
                    'companyWebsite' => $email->client->website ?? null,
                    'logoUrl' => $email->client->logo ?? null,
                ];

                $email->html = view('emails.formal-red-template', $templateData)->render();

                return $email;
            });

            return response()->json([
                'status' => 200,
                'current_page' => $emails->currentPage(),
                'total_emails' => $emails->total(),
                'per_page' => $emails->perPage(),
                'emails' => $emails->items()
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des emails : ' . $e->getMessage(), [
                'filters' => $filters ?? [],
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 500,
                'message' => 'Une erreur est survenue lors de la récupération des emails.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send an email using the template
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function sendMail(Request $request, EmailRepositoryInterface $repository)
    {
        try {
            $request->validate([
                'to' => 'required|email',
                'subject' => 'nullable|string|max:255',
                'message' => 'nullable|string',
                'client_id' => 'nullable|exists:clients,id',
                'contact_id' => 'nullable|exists:contacts,id',
                'document' => 'nullable|string',
                'document_code' => 'nullable|string',
            ]);
            $rawPayload = json_decode($request->getContent(), true);

            $to = $rawPayload['to'] ?? null;
            $subject = $rawPayload['subject'] ?? 'Notification concernant votre document';
            $message = $rawPayload['message'] ?? '';
            $clientId = $rawPayload['client_id'] ?? null;
            $contactId = $rawPayload['contact_id'] ?? null;
            $document = $rawPayload['document'] ?? null;
            $documentCode = $rawPayload['document_code'] ?? null;

            $client = null;
            $contact = null;

            if ($clientId) {
                try {
                    $client = $this->client->findById($clientId);
                } catch (\Exception $e) {
                    Log::warning('Client not found', ['client_id' => $clientId]);
                }
            }

            if ($contactId) {
                try {
                    $contact = $this->contact->getById($contactId);
                } catch (\Exception $e) {
                    Log::warning('Contact not found', ['contact_id' => $contactId]);
                }
            }



            $templateData = [
                'contact' => $contact,
                'client' => $client,
                'document' => $document,
                'documentCode' => $documentCode,
                'to' => $to,
                'subject' => $subject,
                'messageBody' => $message,
                'date' => now()->format('d/m/Y'),
                'recipientName' => $contact->first_name ?? null,
                'recipientEmail' => $to,
                'companyName' => $client->legale_name ?? config('app.name'),
                'companyAddress' => $client->address ?? null,
                'companyEmail' => $client->email ?? config('mail.from.address'),
                'companyPhone' => $client->phone ?? null,
                'companyWebsite' => $client->website ?? null,
                'logoUrl' => $client->logo ?? null,
            ];

            $emailHtml = view('emails.formal-red-template', $templateData)->render();

            $repository->create([
                'client_id' => $clientId,
                'contact_id' => $contactId,
                'subject' => $subject,
                'content' => $message,
                'user_id' => Auth::id(),
            ]);

            $sendResult = $this->mailerService->sendEmail($to, $subject, $emailHtml);

            if (is_array($sendResult) && $sendResult['status'] === 'success') {
                Log::info('Email sent successfully via API', [
                    'to' => $to,
                    'subject' => $subject,
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Email was sent successfully.',
                    'to' => $to,
                    'subject' => $subject,
                    'content' => $emailHtml,
                    'rawHtml' => $emailHtml
                ], 200);
            } else {
                $errorDetails = $sendResult['message'] ?? 'Unknown error';
                $mailerError = $sendResult['mailer_error'] ?? 'No detailed error available';

                Log::error('Failed to send email via API:', [
                    'message' => $errorDetails,
                    'mailer_error' => $mailerError
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send email.',
                    'details' => $errorDetails,
                ], 500);
            }
        } catch (\Throwable $e) {
            Log::error('Error occurred during API email send:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    private function formatFileSize($bytes)
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
