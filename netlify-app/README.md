# Careers React App on Netlify

This directory is the Netlify version of Careers React App. It uses React for
the browser interface, Netlify Functions for authenticated backend operations,
and Supabase Auth/PostgreSQL for hosted accounts and persistent data.

The original PHP application remains at the repository root for local XAMPP
use. Netlify does not execute PHP files.

## Set up the database and authentication

1. Create a Supabase project.
2. In the Supabase SQL Editor, run the complete `supabase-schema.sql` file from
   this directory. It creates the profile, career, assessment, recommendation,
   saved-career and roadmap-progress tables; adds row-level security policies;
   creates a profile on sign-up; and inserts the initial career library.
3. In Supabase Authentication settings, set the site's Site URL to the Netlify
   site URL. Add that same URL, including `/**`, to the allowed redirect URLs.
4. Keep email confirmation enabled for a real deployment. Users with email
   confirmation enabled must confirm their email before they can sign in.

## Configure Netlify

Connect the GitHub repository to Netlify. The root `netlify.toml` builds this
directory, publishes `dist`, and registers the `api` serverless function.

Set these environment variables in Netlify's site environment-variable
settings, with Functions access enabled:

- `VITE_SUPABASE_URL`: the Supabase project URL.
- `VITE_SUPABASE_ANON_KEY`: the Supabase publishable/anon key. This is public
  application configuration and is included in the frontend build.
- `SUPABASE_URL`: the Supabase project URL for server functions.
- `SUPABASE_ANON_KEY`: the Supabase publishable/anon key for token validation.
- `SUPABASE_SERVICE_ROLE_KEY`: the private server-side service-role key. Never
  add it to a `VITE_` variable, source file, or Git commit.

Trigger a new deploy after adding the variables. If Supabase email
confirmation is enabled, configure its email delivery and redirects before
testing registration.

## Run the frontend locally

From this directory:

```powershell
npm install
Copy-Item .env.example .env
# Replace the example values in .env with your Supabase project URL and anon key.
npm run dev
```

The Vite development server serves the frontend. To exercise Netlify Functions
locally, use the Netlify CLI (`npx netlify-cli dev`) and set the private
Supabase variables in your local Netlify environment; do not put the service
role key in a `VITE_` variable.

The local PHP/XAMPP version continues to use its separate private SQLite file.
Account records do not move between the local app and Supabase automatically.
