# Employee Digital Identity & Contact Automation Platform

A lightweight PHP portfolio project for QR-ready employee identity pages and one-tap contact sharing.

Each employee receives a stable profile URL that can be encoded into a printed or digital QR code. The profile provides direct call and email actions plus downloadable vCard contact data.

> This repository is a sanitized portfolio edition. All people, organizations, email addresses, phone numbers, and profile records in this demo are fictional.

## What it demonstrates

- Employee digital identity pages
- QR-ready profile URLs
- One-tap call and email actions
- Standards-based vCard generation
- Mobile-first employee directory
- Data-driven PHP rendering from a structured JSON source
- Apache rewrite routes for clean profile URLs
- Direct-access protection for private application data

## Demo routes

```text
/                         Demo employee directory
/lina-haddad              Employee profile
/vcard/lina-haddad        Download vCard
```

## Stack

- PHP 8+
- HTML5
- CSS3
- Apache / cPanel-compatible `.htaccess`
- JSON-backed profile data

## Local development

From the project directory:

```bash
php -S 127.0.0.1:8080 router.php
```

Then open:

```text
http://127.0.0.1:8080
```

The local router mirrors the clean production routes and blocks direct access to `/data`.

## Security and privacy

The portfolio edition intentionally contains no production employee data or employee photographs.

The application also denies direct web access to the JSON data directory. PHP reads the file server-side while visitors interact only with the rendered profile routes.

See [SECURITY.md](SECURITY.md).

## Portfolio scope

The original business implementation was created for a real employee contact-sharing workflow. This public edition preserves the engineering concept while replacing the organization, identities, contact details, images, and URLs with fictional demo content.

## License

Portfolio review and technical evaluation only. See [LICENSE](LICENSE).
