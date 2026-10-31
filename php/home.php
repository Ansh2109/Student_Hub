<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

$events = db()->query(
    'SELECT title, category, description, event_date, venue
     FROM events
     WHERE status = "open" AND event_date >= NOW()
     ORDER BY event_date
     LIMIT 3'
)->fetch_all(MYSQLI_ASSOC);
$user = current_user();

render_header('Home', 'home');
?>
<section class="home-hero">
    <div class="home-hero-copy">
        <p class="eyebrow">CSPIT · CHARUSAT · AIML</p>
        <h2>Campus life, connected.</h2>
        <p>Discover AIML activities, campus events, and student services in one secure portal.</p>
        <div class="button-group">
            <?php if ($user !== null): ?>
                <a class="btn" href="<?= e(app_url('php/dashboard.php')) ?>">Go to dashboard</a>
                <a class="btn secondary-btn" href="<?= e(app_url('php/events.php')) ?>">Explore events</a>
            <?php else: ?>
                <a class="btn" href="<?= e(app_url('pages/register.html')) ?>">Create your account</a>
                <a class="btn secondary-btn" href="<?= e(app_url('php/login.php')) ?>">Sign in</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="home-hero-art">
        <img class="campus-life-image" src="https://it.cspit.charusat.ac.in/images/about/department.webp" alt="CSPIT campus building at CHARUSAT" referrerpolicy="no-referrer">
        <div class="hero-campus-card"><span>SH</span><strong>StudentHub</strong><small>Department of Artificial Intelligence and Machine Learning</small></div>
        <p class="image-credit">Campus image: <a href="https://it.cspit.charusat.ac.in/" target="_blank" rel="noopener noreferrer">CSPIT, CHARUSAT</a></p>
    </div>
</section>

<section>
    <div class="section-heading">
        <div>
            <p class="eyebrow">What's happening</p>
            <h2>Upcoming events</h2>
        </div>
        <a class="text-link" href="<?= e(app_url('php/events.php')) ?>">View all events <span aria-hidden="true">→</span></a>
    </div>
    <?php if ($events === []): ?>
        <p class="empty-state">There are no upcoming events right now. Check back soon.</p>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($events as $event): ?>
                <article class="event-card home-event-card">
                    <span class="event-category"><?= e($event['category']) ?></span>
                    <h3><?= e($event['title']) ?></h3>
                    <p><?= e($event['description']) ?></p>
                    <p class="event-meta">
                        <time datetime="<?= e(date(DATE_ATOM, strtotime($event['event_date']))) ?>"><?= e(date('D, d M Y · g:i a', strtotime($event['event_date']))) ?></time>
                        <span><?= e($event['venue']) ?></span>
                    </p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="home-feature-strip" aria-label="StudentHub features">
    <article><span aria-hidden="true">01</span><h3>Campus events</h3><p>Discover workshops, sports, and activities around campus.</p></article>
    <article><span aria-hidden="true">02</span><h3>Your profile</h3><p>Keep your student and academic details easy to find.</p></article>
    <article><span aria-hidden="true">03</span><h3>One community</h3><p>Get connected to useful campus information and support.</p></article>
</section>
<?php render_footer(); ?>
