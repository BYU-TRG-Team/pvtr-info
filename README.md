# PVTR License Lookup

Laravel application for public license lookup and license-data administration.

## Local development

Requirements: PHP 8.3+, Composer, Node.js, and npm.

```bash
git clone git@github.com:BYU-TRG-Team/pvtr-info.git
cd pvtr-info
cp .env.example .env
touch database/database.sqlite
composer run setup
php artisan db:seed
composer run dev
```

Open <http://127.0.0.1:8000>. The default local admin is `admin@example.com` / `password`.

Run tests with `composer test`.

## Application usage

- `/` — look up and check a license.
- `/login` — sign in to the admin area.
- `/admin` — upload license data and view import history.
- `/admin/users` — create admin users or change your own password.
- `/complaints/new` — file a public complaint.
- `/admin/complaints` — review and manage complaints.

Imports accept `.xlsx`, `.txt`, and `.tsv` files up to 10 MB. Text files must be tab-delimited. Required headers are:

```text
License # | License prefix | Entity name | Entity type | License status | Email | Expiration date
```

Malformed rows are skipped. Each successful upload is the complete current license snapshot, so omitted licenses are marked non-current.

## Complaint workflow

Public users can file either an invalid-logo complaint or a poor-quality translation complaint. The application records the filing date, derives the logo-license status from the current license data, creates a public complaint reference, and stores the original statement as the first thread message.

After submitting, the complainant receives a private secret link. Anyone with this unguessable link can view that complaint and post follow-up messages, so it should be treated like a password and shared only with the complainant. No public account or email notification is required.

Authenticated administrators can:

- List active or archived complaints and filter them by workflow status.
- Review submitted details and the complete chronological message thread.
- Correct the complaint type or potential-harm category without changing the complainant’s identity, contact information, statement, or supporting evidence.
- Change workflow status and send replies.
- View or copy the complainant’s private link if it needs to be resent.
- Archive and restore complaints in any workflow state.

Archived complaints remain visible through their private links in read-only mode. Neither the complainant nor an administrator can add messages until the complaint is restored.

Complaint data is stored in SQLite. Back up `database/database.sqlite` before deployments and run `php artisan migrate --force` when deploying schema changes.

## Server SSH setup

### Workstation to Bluehost

```bash
ssh-keygen -t ed25519 -C "pvtr-bluehost" -f ~/.ssh/pvtr_bluehost
cat ~/.ssh/pvtr_bluehost.pub | ssh SERVER_USER@SERVER_IP \
  'umask 077; mkdir -p ~/.ssh; cat >> ~/.ssh/authorized_keys'
```

Add to your workstation's `~/.ssh/config`:

```sshconfig
Host pvtr-bluehost
    HostName SERVER_IP
    User SERVER_USER
    IdentityFile ~/.ssh/pvtr_bluehost
    IdentitiesOnly yes
```

Connect with `ssh pvtr-bluehost`.

### Bluehost to GitHub

On the server:

```bash
ssh-keygen -t ed25519 -C "pvtr-bluehost-deploy" -f ~/.ssh/pvtr_github
cat ~/.ssh/pvtr_github.pub
```

Add the public key under **BYU-TRG-Team/pvtr-info → Settings → Deploy keys**. Leave write access disabled.

Add to the server's `~/.ssh/config`:

```sshconfig
Host github-pvtr
    HostName github.com
    User git
    IdentityFile ~/.ssh/pvtr_github
    IdentitiesOnly yes
```

Test and clone:

```bash
ssh -T git@github-pvtr
git clone git@github-pvtr:BYU-TRG-Team/pvtr-info.git
```

## First server setup

Point the site's document root to the repository's `public/` directory.

```bash
cd /path/to/pvtr-info
cp .env.example .env
touch database/database.sqlite
composer install --no-dev --optimize-autoloader
php artisan key:generate
```

Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, and strong `ADMIN_*` values in `.env`, then run:

```bash
php artisan migrate --seed --force
chmod -R ug+rw storage bootstrap/cache database
php artisan optimize
```

## Manual pull deployment

```bash
ssh pvtr-bluehost
cd /path/to/pvtr-info
cp database/database.sqlite database/database.sqlite.$(date +%Y%m%d%H%M%S).bak
php artisan down
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan up
```

Frontend build assets are committed, so Node.js is not required on the server for a normal pull deployment.
