<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('subject')</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #0a0924;
            line-height: 1.6;
        }
        .wrapper {
            width: 100%;
            background-color: #f5f5f5;
            padding: 32px 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            padding: 32px 32px 24px;
            text-align: center;
        }
        .content {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 16px;
            color: #0a0924;
        }
        .body-text {
            font-size: 15px;
            color: #444444;
            margin-bottom: 16px;
        }
        .highlight-box {
            padding: 14px 18px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
        }
        .highlight-success {
            background-color: #f0fdf4;
            border-left: 4px solid #22c55e;
            color: #166534;
        }
        .highlight-warning {
            background-color: #fffdf5;
            border-left: 4px solid #ffe762;
            color: #92400e;
        }
        .highlight-danger {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }
        .highlight-info {
            background-color: #f8f8ff;
            border-left: 4px solid #0a0924;
            color: #0a0924;
        }
        .cta-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0a0924;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            margin: 16px 0;
        }
        .order-meta {
            font-size: 13px;
            color: #888888;
            margin: 16px 0;
        }
        .footer {
            padding: 24px 32px;
            text-align: center;
            border-top: 1px solid #eeeeee;
        }
        .footer-text {
            font-size: 13px;
            color: #999999;
        }
        .footer-contact {
            font-size: 13px;
            color: #999999;
            margin-top: 8px;
        }
        .footer-contact a {
            color: #0a0924;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
                    <img src="https://cdn.tasty.mv/production/images/tasty-logo-black.png" alt="tasty.mv" style="height: 40px;">
                </td></tr></table>
            </div>

            <div class="content">
                @yield('body')
            </div>

            @yield('before-footer')

            <div class="footer">
                <div class="footer-text">Questions? We're happy to help.</div>
                <div class="footer-contact">
                    Email: <a href="mailto:hello@tasty.mv">hello@tasty.mv</a> | Phone: <a href="tel:7777777">7777777</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
