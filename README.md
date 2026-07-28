# StreamVault

**Your open-source companion for discovering, tracking, and enjoying movies and TV shows.**

StreamVault is a full-featured streaming companion built with Laravel 13, Livewire 4, and Tailwind CSS 4. It aggregates metadata from TMDB, resolves playback through third-party embed providers, and gives users a rich set of social and tracking features — all without hosting any video content.

> **Disclaimer:** StreamVault does not host, store, or distribute any video content. All streams are provided by third-party external sources. Metadata is provided by [TMDB](https://www.themoviedb.org/).

---

## Features

### Content Discovery
- **Browse** movies, TV shows, genres, collections, and people
- **Trending & Popular** pages with time-window and media-type filters
- **Discover** page with advanced filtering (year, rating, genre, language)
- **Mood Picker** — get recommendations based on how you feel
- **AI Recommendations** — intelligent content suggestions
- **Trailers Hub** — browse and watch trailers
- **New Releases & Upcoming** — stay up to date
- **Search** with real-time suggestions and API endpoint

### Streaming
- **12+ embed providers** with smart server selection and scoring
- **CineSrc** as primary driver with PostMessage API, autoskip, and autonext
- **Optional HLS direct playback** via CineSrc Stream Resolver
- **Auto-fallback** — switches server automatically if one fails to load
- **Server health indicators** — green/amber/red dots based on provider status
- **Resume playback** — pick up where you left off with timestamp prompt
- **Cross-device sync** — start on your phone, continue on your laptop
- **Auto-next episode** — Netflix-style countdown when an episode ends
- **Keyboard shortcuts** — Space, arrows, F, M, P, N, ? for player control
- **Picture-in-Picture** — keep watching while browsing
- **Streaming availability** — check where content is available on commercial platforms (Netflix, Hulu, etc.)
- **Provider analytics** — tracks success rates, buffering, and load times by region and time of day
- **Pre-warm caching** — background command pre-resolves sources for trending content

### Social & Community
- **Follow system** — follow other users and see their activity
- **Activity feed** — see what your friends are watching and reviewing
- **User profiles** — public profiles with stats, badges, and watch history
- **Watch parties** — create and join shared viewing sessions
- **Direct messaging** — private conversations between users
- **Leaderboard** — compete on watches, reviews, and streaks
- **Movie trivia quiz** — test your knowledge and track scores

### Tracking & Gamification
- **Watch history** with progress tracking and episode state
- **Watchlist** and **Favorites**
- **User collections** — curate your own lists
- **Reviews** — rate and review any title
- **Episode tracking** — mark episodes as watched
- **Badges** — earn achievements for milestones (night owl, binge watcher, etc.)
- **Streaks** — track consecutive days of watching
- **Stats dashboard** — visualize your viewing habits

### Authentication & Security
- Email/password registration with email verification
- Two-factor authentication (TOTP with QR code + recovery codes)
- Passkey/WebAuthn support
- Password confirmation for sensitive actions
- Age-gated adult content with dedicated middleware

### Settings
- Profile management (name, email, avatar)
- Appearance preferences (theme)
- Streaming preferences (quality, autoskip, autonext, default source, excluded providers)
- Security (password, 2FA, passkeys, account deletion)
- Searchable settings navigation

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | PHP 8.4, Laravel 13 |
| **Frontend** | Livewire 4, Alpine.js, Tailwind CSS 4 |
| **Components** | Flux UI 2 (free edition) |
| **Auth** | Laravel Fortify (2FA, passkeys) |
| **Testing** | Pest 4, Larastan 3 |
| **Code Style** | Laravel Pint |
| **Bundler** | Vite 8 |
| **Database** | SQLite (default) / MySQL |

---

## Requirements

- PHP 8.4+
- Composer 2
- Node.js 22+ and npm
- SQLite (default) or MySQL 8+

---

## Installation

```bash
# Clone the repository
git clone https://github.com/khayson/movies.git
cd movies

# Full setup (installs deps, copies .env, generates key, migrates, builds assets)
composer setup

# Start the development server
composer dev
```

The `composer setup` command runs:
1. `composer install`
2. Copies `.env.example` to `.env`
3. Generates application key
4. Runs database migrations
5. `npm install`
6. `npm run build`

The `composer dev` command starts three processes concurrently:
- Laravel dev server (`php artisan serve`)
- Queue listener (`php artisan queue:listen`)
- Vite dev server (`npm run dev`)

### Environment Configuration

Copy `.env.example` and configure as needed:

```env
APP_NAME=StreamVault
APP_URL=http://localhost:8000

# Database (SQLite works out of the box)
DB_CONNECTION=sqlite

# TMDB API (required for content metadata)
TMDB_API_KEY=your_tmdb_api_key

# Optional: CineSrc direct HLS resolver
CINESRC_RESOLVER_URL=http://127.0.0.1:8787
```

### Seeding Demo Data

```bash
php artisan db:seed
```

This creates sample users, content history, reviews, collections, social connections, and badges.

---

## Usage

### Artisan Commands

```bash
# Pre-warm streaming sources for trending content (runs every 15 min via scheduler)
php artisan app:pre-warm-sources

# Send weekly digest emails (runs Mondays at 9:00 AM via scheduler)
php artisan app:send-weekly-digest
```

### Scheduler

Add the Laravel scheduler to your crontab:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Testing

```bash
# Run the full test suite (Pint + Larastan + Pest)
composer test

# Run only Pest tests
php artisan test --compact

# Run a specific test file
php artisan test --compact --filter=StreamingExperience

# Run with coverage
php artisan test --coverage
```

The project includes 147+ tests covering authentication, content browsing, streaming, social features, settings, and API endpoints.

---

## Project Structure

```
app/
├── Console/Commands/       # Artisan commands (digest, pre-warm)
├── Mail/                   # Mailable classes (weekly digest)
├── Models/                 # 18 Eloquent models
├── Services/               # Business logic (TMDB, SourceResolver, badges, etc.)
└── Support/                # Helpers (UserPreferences, SettingsSearch)

config/
└── sources.php             # Streaming provider configuration

database/
├── factories/              # 16 model factories
├── migrations/             # 28 migrations
└── seeders/                # Demo data seeders

resources/views/
├── components/             # Reusable Blade components
├── emails/                 # Email templates
├── layouts/                # App and guest layouts
├── pages/                  # Livewire full-page components (34 pages)
└── partials/               # Shared partials (head, search, etc.)

routes/
├── web.php                 # Web routes
└── console.php             # Scheduled commands

tests/
├── Feature/                # 27 feature tests
└── Unit/                   # 4 unit tests
```

---

## Streaming Architecture

StreamVault uses a provider-agnostic source resolution system:

1. **SourceResolver** scores and ranks 12+ embed providers based on:
   - Static reliability scores
   - User's last-used server and default preference
   - Time-of-day performance analytics
   - Regional success rates
   - Recent failure history

2. **CineSrc** is the primary provider with deep integration:
   - PostMessage API for progress tracking
   - Autoskip intros and autonext episodes
   - Resume from saved position
   - Server-side quality preferences

3. **Provider Analytics** continuously improve recommendations:
   - Success/failure/buffering events tracked per provider, region, and hour
   - Data feeds back into server scoring
   - Health indicators shown to users

4. **Pre-warm Caching** resolves sources for trending content in the background, so playback starts instantly when users click.

---

## API

StreamVault exposes a lightweight API:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/search?q={query}` | GET | Search movies and TV shows |
| `/api/media/{type}/{id}` | GET | Get media details |
| `/api/media/{type}/{id}/watchlist` | POST | Toggle watchlist |
| `/api/media/{type}/{id}/favorite` | POST | Toggle favorite |

---

## CI/CD

GitHub Actions runs on every push to `main` and on pull requests:

- PHP 8.4 on Ubuntu
- Node.js 22
- Full `composer setup`
- Pint lint check
- Larastan static analysis
- Pest test suite

---

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run the test suite (`composer test`)
5. Commit your changes
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

Please ensure all tests pass and code follows the project's style (enforced by Pint).

---

## License

This project is open-source software. See the [LICENSE](LICENSE) file for details.

---

## Credits

- Content metadata provided by [TMDB](https://www.themoviedb.org/)
- Built with [Laravel](https://laravel.com/), [Livewire](https://livewire.laravel.com/), and [Tailwind CSS](https://tailwindcss.com/)
- UI components by [Flux UI](https://fluxui.dev/)
- Icons by [Heroicons](https://heroicons.com/)
