<!-- formal-red-template.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ $subject ?? 'Notification concernant votre document' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@100;200;300;400;500;600;700;800;900" rel="stylesheet" type="text/css">
    <style>
        /* Base Styles */
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: #ffffff;
            font-family: 'Open Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        a {
            color: #f65c03;
            text-decoration: underline;
        }

        .container {
            max-width: 680px;
            margin: 0 auto;
        }

        /* Header Styles */
        .header {
            background-color: #f7f1ed;
            border-radius: 0;
            text-align: center;
        }

        .header-content {
            padding: 0;
        }

        .logo {
            max-width: 544px;
            width: 100%;
            height: auto;
        }

        .header-image {
            max-width: 578px;
            width: 100%;
            height: auto;
        }

        /* Hero Section */
        .hero {
            background-color: #f7f1ed;
            border-radius: 0 0 20px 20px;
            padding-bottom: 30px;
        }

        .hero-content {
            display: flex;
            flex-wrap: wrap;
        }

        .hero-text {
            width: 50%;
            padding-left: 55px;
            padding-top: 80px;
        }

        .hero-heading {
            color: #000000;
            font-size: 55px;
            font-weight: 800;
            letter-spacing: -2px;
            line-height: 120%;
            text-align: left;
            margin: 0;
        }

        .hero-image {
            width: 50%;
            text-align: center;
        }

        .coffee-cup {
            max-width: 259px;
            width: 100%;
            height: auto;
        }

        /* Main Content */
        .main-content {
            padding: 40px 30px 30px;
        }

        .text-content {
            color: #333333;
            font-size: 16px;
            line-height: 150%;
            text-align: left;
        }

        .quote-image {
            max-width: 403px;
            width: 100%;
            height: auto;
            margin: 20px 0;
        }

        .section-heading {
            color: #000000;
            font-size: 20px;
            font-weight: 700;
            line-height: 120%;
            text-align: left;
            margin: 20px 0;
        }

        .highlight {
            color: #f65c03;
            font-weight: bold;
        }

        .contact-info {
            color: #000000;
        }

        /* CTA Button */
        .cta-button {
            display: inline-block;
            background-color: #f65c03;
            color: #ffffff !important;
            font-weight: 600;
            font-size: 16px;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            margin: 20px 0;
        }

        /* Document Info Section */
        .document-info {
            background-color: #f7f7f7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }

        .document-title {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .document-item {
            display: flex;
            align-items: center;
            padding: 5px 0;
        }

        .document-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .document-name {
            font-size: 14px;
        }

        /* Footer */
        .footer-top {
            background-color: #000000;
            border-radius: 20px 20px 0 0;
            padding: 20px 60px;
            display: flex;
            flex-wrap: wrap;
        }

        .footer-tagline {
            width: 58%;
            color: #f7f1ed;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 3px;
            line-height: 120%;
            padding: 20px 10px 5px;
        }

        .social-links {
            width: 42%;
            text-align: right;
            padding: 20px 0;
        }

        .social-icon {
            width: 32px;
            height: auto;
            margin-left: 4px;
        }

        .footer-middle {
            background-color: #000000;
            padding: 5px 60px;
        }

        .divider {
            border-top: 1px solid #3a3a3a;
            margin: 10px 0;
        }

        .footer-heading {
            color: #ffffff;
            font-size: 18px;
            font-weight: 400;
            line-height: 120%;
            margin: 20px 0;
        }

        .footer-text {
            color: #ffffff;
            font-size: 15px;
            line-height: 120%;
            margin: 15px 0 5px;
        }

        .footer-bottom {
            background-color: #000000;
            display: flex;
            flex-wrap: wrap;
        }

        .footer-link {
            width: 33.33%;
            text-align: center;
            padding: 15px 20px 20px;
        }

        .footer-link a {
            color: #f7f1ed;
            font-size: 13px;
            text-decoration: underline;
        }

        /* Responsive Styles */
        @media (max-width: 700px) {
            .hero-text, .hero-image {
                width: 100%;
            }

            .hero-text {
                padding: 20px 30px;
            }

            .hero-heading {
                font-size: 40px;
                text-align: center;
            }

            .footer-tagline, .social-links {
                width: 100%;
                text-align: center;
            }

            .footer-top, .footer-middle {
                padding: 20px 30px;
            }

            .footer-link {
                font-size: 11px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="header-content">
                @if(isset($client->logo) && !empty($client->logo))
                    <img src="{{ $client->logo }}" class="logo" alt="{{ $client->name ?? 'Company Logo' }}" title="{{ $client->name ?? 'Company Logo' }}">
                @else
                    <h1 style="padding: 30px 15px; margin: 0; color: #f65c03; font-weight: 700;">{{ $client->name ?? config('app.name') }}</h1>
                @endif
            </div>
        </div>

        <!-- Hero Section -->
        <div class="hero">
            <div class="hero-content">
                <div class="hero-text">
                    <h1 class="hero-heading">{{ $subject ?? 'Notification concernant votre document' }}</h1>
                </div>
                <div class="hero-image">
                    <!-- Document icon or relevant image could go here -->
                    <img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyNTkiIGhlaWdodD0iMjU5IiB2aWV3Qm94PSIwIDAgMjQgMjQiIGZpbGw9Im5vbmUiIHN0cm9rZT0iI2Y2NWMwMyIgc3Ryb2tlLXdpZHRoPSIxLjUiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCI+PHBhdGggZD0iTTE0IDJINmE2IDYgMCAwMDAgMTJoMTJhNiA2IDAgMDA2LTYgNCA0IDAgMDAtNC00aC04YTQgNCAwIDEwMCA4aDJjMiAwIDQtMiA0LTRTMTYgNiAxNCA2aC0yIj48L3BhdGg+PC9zdmc+" class="coffee-cup" alt="Document notification" title="Document notification">
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="text-content">
                <!-- @if(isset($contact) && isset($contact->first_name))
                    <p>Bonjour {{ $contact->first_name }} {{ $contact->last_name ?? '' }},</p> -->


                    @if(isset($messageBody))
    <div class="text-content">
        {!! nl2br($messageBody) !!}
    </div>
@endif

                    <p>Nous vous informons qu'un document est disponible pour vous.</p>
                @endif

                @if(isset($document) && !empty($document))
                    <div class="document-info">
                        <div class="document-title">Informations du document</div>
                        <div class="document-item">
                            <svg class="document-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#f65c03" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                            <span class="document-name">{{ $document->title ?? $document->name ?? 'Document' }}</span>
                        </div>
                        @if(isset($document->created_at))
                            <div class="document-item">Date: {{ \Carbon\Carbon::parse($document->created_at)->format('d/m/Y') }}</div>
                        @endif
                        @if(isset($document->code) || isset($documentCode))
                            <div class="document-item">Code: {{ $document->code ?? $documentCode }}</div>
                        @endif
                    </div>
                @endif

                @if(isset($ctaText) && isset($ctaUrl))
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{ $ctaUrl }}" class="cta-button" target="_blank">{{ $ctaText }}</a>
                    </div>
                @else
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="#" class="cta-button" target="_blank">Voir le document</a>
                    </div>
                @endif

                <p>
                    Cordialement,<br><br>
                    <span class="contact-info">
                        <strong>{{ $client->name ?? config('app.name') }}</strong><br>
                        @if(isset($client->address))
                            {{ $client->address }}<br>
                        @endif
                        @if(isset($client->phone))
                            {{ $client->phone }}<br>
                        @endif
                        @if(isset($client->email))
                            <a href="mailto:{{ $client->email }}">{{ $client->email }}</a>
                        @endif
                    </span>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-top">
            <div class="footer-tagline">
                {{ $client->name ?? config('app.name') }}
            </div>
        </div>

        <div class="footer-middle">
            <div class="divider"></div>
            <h3 class="footer-heading">Envoyé le {{ \Carbon\Carbon::now()->format('d/m/Y') }}</h3>
            <div class="divider"></div>
            @if(isset($client->address))
                <p class="footer-text">{{ $client->address }}</p>
            @endif
        </div>

        <div class="footer-bottom">
            <div class="footer-link">
                <a href="{{ isset($client->website) ? $client->website : '#' }}" target="_blank" rel="noopener">Site web</a>
            </div>
            <div class="footer-link">
                <a href="{{ isset($client->email) ? 'mailto:' . $client->email : '#' }}">Contact</a>
            </div>
            <div class="footer-link">
                <a href="#">Désabonnement</a>
            </div>
        </div>
    </div>
</body>
</html>
