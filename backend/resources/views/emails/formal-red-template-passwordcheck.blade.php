<!-- password-reset-template.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <title>{{ $subject ?? 'Password Reset Request' }}</title>
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

        .notification-icon {
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

        /* User Info Section */
        .user-info {
            background-color: #f7f7f7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
        }

        .user-title {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .user-item {
            display: flex;
            align-items: center;
            padding: 5px 0;
        }

        .user-icon {
            width: 20px;
            height: 20px;
            margin-right: 10px;
        }

        .user-name {
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
                    <h1 class="hero-heading">{{ $subject ?? 'Password Reset Request' }}</h1>
                </div>
                <div class="hero-image">
                    <!-- Key/lock icon for password reset -->
                    <svg class="notification-icon" xmlns="http://www.w3.org/2000/svg" width="259" height="259" viewBox="0 0 24 24" fill="none" stroke="#f65c03" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        <circle cx="12" cy="16" r="1"></circle>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="text-content">
                @if(isset($recipientName))
                    <p>Bonjour {{ $recipientName }},</p>
                @endif

                @if(isset($messageBody))
                    <div class="text-content">
                        {!! nl2br($messageBody) !!}
                    </div>
                @endif

                <!-- User Info Section - Replaces Document Info -->
                @if(isset($userInfo))
                    <div class="user-info">
                        <div class="user-title">Informations de l'utilisateur</div>
                        <div class="user-item">
                            <svg class="user-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#f65c03" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span class="user-name">{{ $userInfo->full_name ?? 'User' }}</span>
                        </div>
                        @if(isset($userInfo->email))
                            <div class="user-item">Email: {{ $userInfo->email }}</div>
                        @endif
                        @if(isset($date))
                            <div class="user-item">Date de demande: {{ $date }}</div>
                        @endif
                    </div>
                @endif

                @if(isset($ctaText) && isset($ctaUrl))
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{ $ctaUrl }}" class="cta-button" target="_blank">{{ $ctaText }}</a>
                    </div>
                @else
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{{ route('admin.users.index') }}" class="cta-button" target="_blank">Gérer les utilisateurs</a>
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
            <h3 class="footer-heading">Envoyé le {{ $date ?? \Carbon\Carbon::now()->format('d/m/Y') }}</h3>
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
                <a href="#">Confidentialité</a>
            </div>
        </div>
    </div>
</body>
</html>
