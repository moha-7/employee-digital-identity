# Security Notes

This portfolio edition uses fictional data only.

## Data exposure controls

Employee records live in `data/employees.json`, which is intended for server-side reads only.

- Apache deployments deny direct requests to `/data` through `.htaccess`.
- The included PHP development router also returns HTTP 403 for `/data` and `/backups` paths.
- Directory listing is disabled for Apache deployments.
- Debug/test files and historical backups from the source implementation are not included.

## Production guidance

For a real employee deployment, keep personally identifiable information limited to the fields required for the contact experience, review consent and retention requirements, and avoid exposing a browsable employee directory unless that is an explicit business requirement.
