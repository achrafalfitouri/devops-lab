<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quote Invoice</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }

        .container {
            width: 80%;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .totals-right p:nth-child(3) {
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .header-left {
            display: flex;
            align-items: flex-start;
            border-bottom: none;
            align-items: flex-start;
            flex-direction: column;
        }

        .header-left .date {
            font-size: 12px;
            color: #333;
            margin-right: 15px;
        }

        .header-left img {
            width: 50px;
            height: 50px;
        }

        .header-right {
            font-size: 12px;
            text-align: right;
        }

        .client-info {
            margin-bottom: 20px;
            font-size: 14px;
            color: #333;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 14px;
        }

        .table th {
            background-color: #f9f9f9;
        }

        .totals {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .totals-left {
            width: 48%;
            color: #333;
        }

        .totals-right {
            width: 48%;
            text-align: right;
            margin-top: 20px;
        }

        .totals-right {
            width: 90%;
            margin-top: -70px;
            text-align: right;
            margin: 10px;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            text-align: center;
            color: #777;
        }

        .header-middle {
            flex: 1;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .footer p {
            margin: 0;
        }

        .header-right p {
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="header-middle">
                Vuexy - Vuejs Admin Dashboard Template
            </div>
            <div class="header-left">
                <p class="date">{{ now()->format('d/m/Y H:i') }}</p>
                <img src="/public/icone/default.jpg" alt="Printer Icon" width="50" height="50">

            <div class="header-right">
            <p>Date de validité:9</p>
            </div>
        </div>

        <!-- Client Info Section -->
        <div class="client-info">
            <p>{{ $invoiceCredit['customer_name'] ?? 'N/A' }}</p>
            <p>{{ $invoiceCredit['customer_address'] ?? 'N/A' }}</p>
        </div>

        <!-- Table Section -->
        <table class="table">
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th>PU</th>
                    <th>Qte</th>
                    <th>Montant HT</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>
                @if(!empty($invoiceCredit['items']))
                @foreach ($invoiceCredit['items'] as $item)
                <tr>
                    <td>{{ $item['description'] ?? 'N/A' }}</td>
                    <td>{{ $item['price'] ?? 0 }} DH</td>
                    <td>{{ $item['quantity'] ?? 0 }}</td>
                    <td>{{ $item['amount'] ?? 0 }} DH</td>
                    <td>{{ $item['category'] ?? 'N/A' }}</td>
                </tr>
                @endforeach
                @else
                <tr>
                    <td colspan="5" style="text-align: center;">No items available</td>
                </tr>
                @endif
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals">
            <div class="totals-left">
                <p>Total TTC en lettres: {{ $invoiceCredit['total_in_words'] ?? 'N/A' }}</p>
                <p>Commentaire: {{ $invoiceCredit['comment'] ?? 'N/A' }}</p>
            </div>
            <div class="totals-right" style="margin-top:-500px;">
                <p>Total HT: {{ $invoiceCredit['total_ht'] ?? 0 }} DH</p>
                <p>Remise: {{ $invoiceCredit['discount'] ?? 0 }} DH</p>
                <p>TVA: {{ $invoiceCredit['tva'] ?? 0 }} DH</p>
                <p>Total TTC: {{ $invoiceCredit['total_ttc'] ?? 0 }} DH</p>
            </div>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            <p>Nom juridique: {{ $invoiceCredit['customer_name'] ?? 'N/A' }}, ICE, IF, TP, RC?, Téléphone, Email, Site web, Adresse</p>
        </div>
    </div>
</body>

</html>