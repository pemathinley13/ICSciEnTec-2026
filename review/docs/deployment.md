# Deployment & Local Setup

## Running it locally (for testing/demoing)

Requires PHP 8.0+ and MySQL 5.7+/8.0 (or MariaDB 10.3+). On macOS:

```bash
brew install php mysql
brew services start mysql
```

1. **Create the database and import the schema:**
   ```bash
   mysql -u root -e "CREATE DATABASE icscientec_review CHARACTER SET utf8mb4;"
   mysql -u root icscientec_review < database/schema.sql
   mysql -u root icscientec_review < database/seed.sql
   ```

2. **Configure the app:**
   ```bash
   cp config/config.sample.php config/config.php
   # edit config/config.php — at minimum set db.user / db.pass if not using
   # a passwordless local root account
   ```

3. **Create your first Organizing Committee (admin) account** — do this instead
   of hand-editing a password hash into SQL:
   ```bash
   php database/create_admin.php
   ```

4. **Run it:**
   ```bash
   php -S localhost:8000 -t public
   ```
   Visit `http://localhost:8000` — it redirects to the login page. Register a
   new author account, submit a paper, and it should show up on the author
   dashboard with status "submitted" and a status timeline.

This exact flow (register → submit with file upload → view with timeline,
plus duplicate-email and wrong-password rejection) has been run end-to-end
against a local MySQL instance as part of building Phase A — see the top-level
conversation for the verification transcript. No further local setup steps
should be needed beyond the four above.

## Deploying to the college server (cPanel/Plesk shared hosting)

This app has **zero build step and no Composer/CLI dependency at runtime** —
it's deployed by uploading files as-is. Steps below assume a cPanel-style
control panel, which is almost certainly what's running the existing
WordPress site at icscientec.cst.edu.bt.

1. **Create a subdomain** for the portal, e.g. `review.icscientec.cst.edu.bt`
   (cPanel → Domains/Subdomains). When cPanel asks for a document root, don't
   accept the default — point it at a path that doesn't exist yet, e.g.
   `/home/<cpaneluser>/icscientec-review/public` (see step 2 for why).
2. **Upload the whole project** (via File Manager or FTP/SFTP) to
   `/home/<cpaneluser>/icscientec-review/` — i.e. one level *above* the
   subdomain's document root from step 1. This keeps `config/`, `src/`,
   `storage/`, and `database/` permanently outside anything the web server
   will ever serve directly, regardless of `.htaccess` — the strongest
   version of the isolation the app already assumes locally.
   - Skip uploading `config/config.php` (it doesn't exist in the repo
     anyway) and the contents of `storage/uploads/` (starts empty).
3. **Set the PHP version**: cPanel → "MultiPHP Manager" / "Select PHP
   Version" → choose PHP 8.0 or newer for the subdomain, and confirm
   `pdo_mysql`, `mbstring`, and `fileinfo` are enabled (usually on by default).
4. **Create the database**: cPanel → "MySQL Databases" → create a database
   and a database user with a strong password, then add that user to the
   database with **All Privileges**. cPanel will prefix both names with your
   account username (e.g. `cpaneluser_icscientec_review`).
5. **Create `config/config.php`** directly on the server (File Manager →
   copy `config.sample.php` → rename) and fill in: the DB name/user/password
   from step 4, `app.base_url` = `https://review.icscientec.cst.edu.bt`, and
   the `mail.*` SMTP settings (see below).
6. **Import the schema**: cPanel → "phpMyAdmin" → select the new database →
   Import tab → upload `database/schema.sql`, then repeat for
   `database/seed.sql`.
7. **Create the first admin account** — two options:
   - If cPanel offers a **Terminal** (under "Advanced"), or you have SSH: run
     `php database/create_admin.php` from the project root.
   - Otherwise, use the web-based fallback built for exactly this: in
     `config.php`, temporarily set `app.setup_token` to a long random string,
     visit `https://review.icscientec.cst.edu.bt/setup_admin.php`, fill in
     the form (using that same token). **Immediately afterward**, blank
     `setup_token` back to `''` (or delete `public/setup_admin.php`
     entirely) — it refuses to run at all once an admin account exists, but
     there's no reason to leave the door even partially open.
8. **Enable HTTPS**: cPanel usually offers free AutoSSL (Let's Encrypt) for
   the subdomain — turn it on, then set `app.https = true` in `config.php` so
   session cookies get the `Secure` flag.
9. **Confirm `storage/` truly isn't reachable**: with the document-root
   layout from step 1/2, there's no URL under the subdomain that can reach
   it at all — worth a quick sanity check anyway by trying
   `https://review.icscientec.cst.edu.bt/../storage/uploads/`, which should
   404.
10. **Visit the subdomain** — it should redirect to `/auth/login.php`. Log
    in as the admin account from step 7 and confirm `/admin/dashboard.php`
    loads.

**SMTP**: since RUB's email already runs on Google Workspace (confirmed
while testing locally), the same approach that worked there applies here —
generate a Gmail App Password for whichever address should send ICSciEnTec's
notifications (ideally an institutional one like
`secretary_icscientec.cst@rub.edu.bt`, not a personal account), and set
`mail.driver = 'smtp'` with `smtp_host = 'smtp.gmail.com'`, port `587`,
`smtp_secure = 'tls'`, and that address as both `smtp_user` and `from_email`.

**The marketing site** (`icscientec2`) deploys separately — it's pure static
HTML, so it just needs its files uploaded wherever it's meant to live (the
existing WordPress host's document root, or its own subdomain). The only
thing to update after the portal is live: swap every
`http://localhost:8000/auth/login.php` reference in `welcome.html`,
`abstract-submission-guidelines.html`, and `full-paper-submission-guidelines.html`
to the real portal URL, e.g. `https://review.icscientec.cst.edu.bt/auth/login.php`.

## Pre-launch checklist (verify with the college's IT before real submissions)

- [ ] PHP version ≥ 8.0, with the `pdo_mysql` extension enabled.
- [ ] `upload_max_filesize` and `post_max_size` in `php.ini` are large enough
      for a full manuscript + figures (config currently caps app-side at 20MB
      — raise both together if you raise the app's `uploads.max_size_bytes`).
- [ ] HTTPS is available — then set `app.https = true` in `config.php` so
      session cookies get the `Secure` flag.
- [ ] Outbound SMTP is reachable from the server for
      `secretary_icscientec.cst@rub.edu.bt` (or whichever address is
      configured), and SPF/DKIM are set up on the sending domain — otherwise
      decision/notification emails may land in spam or get rejected.
      **Not needed yet** — email is still on the `log` driver (Phase A/B);
      this only matters once a later phase switches `mail.driver` to `smtp`.
- [ ] A database + `storage/uploads/` backup policy exists. This system
      becomes the sole system of record for submissions and reviews once
      live — confirm with IT what backs it up and how often.
- [ ] Decide whether track chairs may review/decide on their own track's
      papers (self-COI). Not yet enforced in code — flagging for a policy
      answer before Phase B builds the assignment screen.

## What's built so far (Phases A + B)

**Phase A**
- Full schema (`database/schema.sql`) for all 14 workflow steps, not just
  what Phase A uses — later phases add pages, not tables.
- Auth: register (author-only self-signup), login, logout, session-based
  CSRF on every form.
- RBAC (`src/Auth/Rbac.php`): `requireRole()` guard, ready for every
  role-specific page later phases add.
- Author flow: submission form (title, track, abstract with live word count,
  3–5 keywords, dynamic co-author rows, manuscript upload), dashboard listing,
  detail view with full status timeline.
- `StatusTransitionService`: the one place every status change goes through,
  so `audit_log` and the matching email fire together, every time.
- `EmailService`: logs every notification to `email_log`; safe `log` driver
  until a later phase wires real SMTP.

**Phase B**
- `admin/dashboard.php` + `admin/users.php` — pulled forward from Phase E's
  full admin build, because Phase B is untestable without a way to grant
  reviewer/track-chair/etc. roles to an existing account. Lets an Organizing
  Committee admin grant/revoke any role (with a track for Track Chair) for
  any registered user. The fuller admin area (editions, audit-log browser,
  CSV export) is still Phase E.
- Track Chair: `dashboard.php` (submissions across the track(s) you chair),
  `desk_check.php` (pass → `under_review`, fail → `desk_rejected` with a
  required reason, which emails the author and starts their 7-day appeal
  clock), `assign_reviewers.php` (invite ≥2 reviewers with an optional due
  date; already-assigned reviewers are shown, not re-invitable).
- Reviewer: `dashboard.php` (your assignments and their status), `coi.php`
  (blinded title/abstract/keywords only, no author names — declare a
  conflict of interest, and if none, acknowledge the confidentiality/AI-use
  policy to accept; declining is always allowed). Every response — accept or
  decline — writes one `coi_declarations` row, per spec step 3.
- New `reviewer_assignments.policy_ack_at` column (`database/migrations/
  001_add_assignment_policy_ack.sql`, already folded into `schema.sql` too)
  — records when a reviewer accepted and acknowledged the policy, since that
  happens at assignment-acceptance time (step 3/10), before any `reviews`
  row exists.
- `ReviewAssignmentService`: assignment + COI/accept-decline logic, mirroring
  how `StatusTransitionService` centralizes submission status changes.

Verified end-to-end against a local MySQL instance: admin grants reviewer/
track-chair roles → track chair desk-checks a real submission (pass) →
assigns 2 reviewers → one reviewer accepts (no conflict, policy
acknowledged) → the other declares a conflict of interest and declines —
with the full `audit_log` and `email_log` trail confirmed correct in the
database after each step.

**Phase C**
- `reviewer/review_form.php` — scores (novelty/methodology/ethics, 1–5),
  strengths/weaknesses (author-visible), confidential comments
  (committee-only), a recommendation (accept/minor/major/reject), and a
  re-confirmed AI-policy checkbox at submission time (`reviews.ai_policy_ack`
  — a second, later checkpoint from the one at assignment-acceptance in
  Phase B, not a duplicate of it).
- `public/download.php` — the one ACL-guarded path every manuscript download
  goes through: allows the corresponding author, any reviewer with an
  accepted/completed assignment, the track chair(s) of that track, and
  TPC/publications-chair/admin: denies everyone else (e.g. a reviewer who
  declined), and logs every successful download to `audit_log`.
- Auto-notification: the moment the *last* outstanding accepted review comes
  in, the track chair(s) get a "ready to consolidate" email — no polling
  needed.
- `track_chair/consolidate.php` — shows every completed review side by side
  (including the confidential committee-only comments, correctly withheld
  from what an author would ever see), flags "significant disagreement"
  (recommendations spanning accept→reject) and suggests requesting a 3rd
  reviewer, then consolidates and moves the submission to
  `awaiting_tpc_decision` — which now emails every TPC member.
- `StatusTransitionService`'s notification map was generalized to support
  different recipient groups (author vs. TPC vs., later, publications chair)
  instead of always emailing the author — needed once a transition's
  audience stopped being "always the author".

Verified end-to-end again: assigned a replacement 2nd reviewer after the
first Phase B decline, both reviewers submitted deliberately conflicting
recommendations (accept vs. reject), confirmed the "reviews complete" email
fired to the chair automatically, confirmed a declined reviewer is denied a
manuscript download while an accepted one succeeds (with an audit-log row),
confirmed the consolidation screen correctly flagged the disagreement, and
confirmed consolidating sent the submission to `awaiting_tpc_decision` with
the TPC member notified — with a complete, correctly-ordered `audit_log`
trail from submission through consolidation.

**Phase D**
- Real SMTP sending is now implemented (`EmailService::sendViaSmtp()`) using
  a vendored copy of PHPMailer (`vendor/phpmailer/` — PHPMailer.php, SMTP.php,
  Exception.php, MIT-licensed, no Composer needed at runtime). Still defaults
  to `mail.driver = 'log'` in `config.php` since real SMTP credentials for
  the college mail server aren't confirmed — flip that one setting plus
  `smtp_host`/`smtp_user`/`smtp_pass`/`from_email` once they are, and every
  page that already calls `EmailService::send()` starts actually sending
  without any other code changing.
- `tpc/dashboard.php` + `tpc/decide.php` — every TPC member sees every
  submission awaiting a decision (not track-scoped, unlike track chairs),
  including the track chair's consolidation notes and the disagreement flag.
  Recording accept / minor revisions / major revisions / reject writes a
  `tpc_decisions` row and emails the author with the decision plus each
  reviewer's strengths/weaknesses (never the confidential committee-only
  comments).
- `pubchair/dashboard.php` + `pubchair/screening.php` — manual entry of an
  externally-run similarity percentage (Turnitin/iThenticate-style), which is
  auto-compared against the configured threshold (`integrity_screenings`);
  a pass moves the paper to `accepted` with camera-ready instructions
  emailed to the author, a flag notifies the whole review committee
  (publications chair + track chair + TPC, de-duplicated if one person holds
  more than one of those roles) and the same screening page then handles
  resolving a flagged case (clear & accept / request major revisions /
  reject).
- One `tpc_decision` email template now covers all four TPC outcomes *and*
  a flagged-screening resolution, adapting its wording from a `$decision`
  variable rather than needing four near-identical templates.
- `StatusTransitionService`'s recipient map grew a third group,
  `committee_review` (publications chair + that track's chair(s) + every TPC
  member), alongside `author` and `tpc`.

Verified end-to-end once more, including a bug the tests actually caught:
ran a submission all the way from `awaiting_tpc_decision` → TPC accept →
publications-chair screening (passing similarity) → `accepted`, with the
author correctly notified at each step and a complete audit trail. Separately
exercised the *flagged* branch on synthetic test submissions and initially
hit a real `Data too long for column 'outcome'` DB error — the resolution
page was writing a full status name into a `VARCHAR(20)` column meant for a
short status word. Fixed by mapping resolutions to short outcome labels
(`cleared`/`flagged`/`rejected`) separately from the submission status, then
re-verified all three resolution paths (clear & accept, major revisions,
reject) work correctly. Also caught and fixed a duplicate-email issue where
someone holding two committee roles (e.g. publications chair *and* TPC
member) got the same flagged-screening email twice — recipients are now
de-duplicated by user ID before sending. Also confirmed the new SMTP code
path loads PHPMailer correctly and fails gracefully (logged, not a crash)
against an unreachable host, proving the plumbing is correct ahead of real
credentials being available.

**Phase E (final phase)**
- **Revisions**: `author/upload_revision.php` — a minor revision goes straight
  back to the TPC (no re-review); a major revision starts a fresh review
  round. This needed a real schema change: `reviewer_assignments` originally
  had a hard one-assignment-ever-per-reviewer-per-submission constraint,
  which made it impossible to validly re-invite the same reviewer for a
  second round. Migration `002` (`submissions.review_round` +
  `reviewer_assignments.round`, unique key widened to include the round)
  fixes this; `ReviewAssignmentService`/`Review` got round-scoped query
  variants so consolidation and TPC decision screens only ever act on the
  *current* round's reviews, while full history stays visible on the
  assignments page.
- **Camera-ready**: `author/camera_ready.php` (upload + four required
  confirmations: PDF eXpress, copyright eCF, registration, presentation) and
  `pubchair/camera_ready_review.php` (approve / request revision / mark
  published). Passing integrity screening now auto-chains straight to
  `camera_ready_pending` instead of leaving the paper sitting at a bare
  `accepted` status.
- **Appeals**: `author/appeal.php` (only shown within the 7-day window from
  `notified_at`, one appeal per submission) and `tpc/appeals.php` +
  `tpc/resolve_appeal.php` (uphold → reopens to either `under_review` or
  `awaiting_tpc_decision`, TPC's choice; or deny, terminal).
- **Admin**: `admin/tracks.php`, `admin/editions.php` (create + switch the
  active edition — everything else in the app scopes to whichever edition is
  active), and `admin/audit_log.php` (paginated browser + CSV export,
  optionally filtered to one submission — the spec's "available to IEEE on
  request" requirement).

Verified end-to-end a third time, covering the hardest case on purpose: ran
one submission through a full major-revision cycle — round 1 review → TPC
requests major revisions → author resubmits → **the same two reviewers get
validly re-invited for round 2** (proving the new round-tracking migration
actually works, not just that it doesn't error) → round 2 review →
consolidation (confirmed it only showed round 2's reviews, not stale round-1
ones) → TPC accept → screening pass → camera-ready → approved → published,
with a complete, correctly-ordered audit trail across all ~30 transitions and
every email firing to the right recipient at each step. Separately verified
the appeals path end-to-end (desk-reject → author appeals → TPC upholds →
submission correctly reopens straight to `under_review`, skipping desk check)
and the new admin pages (track/edition creation, active-edition switching,
CSV export). One real bug caught and fixed along the way: `fputcsv()`'s
newer PHP versions require its escape-character parameter explicitly, or a
deprecation warning gets written into the CSV output itself — fixed by
passing it explicitly.

This completes all five phases of the approved plan. The system now covers
the full 14-step IEEE-aligned peer-review process end-to-end: submission,
desk check, reviewer assignment with COI, double-anonymous review,
consolidation with disagreement/3rd-reviewer detection, TPC decision,
integrity screening, revisions with proper re-review rounds, camera-ready,
publication, and appeals — with an unbroken audit trail and email
notifications at every step (ready for real SMTP the moment credentials are
available).
