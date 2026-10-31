<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

render_header('About StudentHub');
?>
<section class="about-hero">
    <p class="eyebrow">About StudentHub</p>
    <h2>Campus life, connected.</h2>
    <p>StudentHub brings student profiles, campus events, and useful information together in one welcoming place.</p>
</section>
<section>
    <div class="section-heading">
        <div>
            <p class="eyebrow">Our purpose</p>
            <h2>Our mission</h2>
        </div>
    </div>
    <article class="about-card">
        <h3>Empowering students</h3>
        <p>We make it easier for students to find campus activities, keep track of their academic information, and stay connected with their community.</p>
    </article>
</section>
<section>
    <div class="section-heading">
        <div>
            <p class="eyebrow">Where we're going</p>
            <h2>Our vision</h2>
        </div>
    </div>
    <article class="about-card">
        <h3>A more connected campus</h3>
        <p>We aim to provide a clear, accessible digital home for campus news, student services, and events.</p>
    </article>
</section>
<?php render_footer(); ?>
