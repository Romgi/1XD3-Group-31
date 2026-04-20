<?php
declare(strict_types=1);

$pageTitle = "Home";
$activePage = "home";

require __DIR__ . "/includes/header.php";
?>
<section class="wireframe-card" aria-labelledby="about-band">
    <h2 id="about-band">About the McMaster Concert Band</h2>

    <div class="wireframe-split">
        <p>
            The McMaster University Concert Band (MCB), under the direction of Joseph Resendes, has
            been a leading ensemble within the School of the Arts and the Hamilton community for
            years. The ensemble is comprised of enthusiastic and talented wind, brass, and percussion
            players from many disciplines across campus. Our mission is to prepare and perform quality
            wind band literature while building rewarding artistic experiences.
        </p>
        <img
            class="feature-image wireframe-hero"
            src="<?= e(appUrl("assets/images/large.webp")); ?>"
            alt="McMaster Concert Band performing together">
    </div>

    <div class="wireframe-icon-row" aria-label="Concert band gallery">
        <img class="gallery-image wireframe-thumb" src="<?= e(appUrl("assets/images/small1.webp")); ?>" alt="Concert band rehearsal moment">
        <img class="gallery-image wireframe-thumb" src="<?= e(appUrl("assets/images/small2.webp")); ?>" alt="Concert band section close-up">
        <img class="gallery-image wireframe-thumb" src="<?= e(appUrl("assets/images/small3.webp")); ?>" alt="Concert band performance detail">
    </div>

    <div class="wireframe-copy">
        <p>
            Students are encouraged to take concert band for credit. The MCB rehearses once per week
            and performs regular concerts in addition to collaborative performances and community
            engagements scheduled throughout the year.
        </p>
        <p>
            Whether you want to be challenged as a player or just want to have fun playing music,
            the McMaster University Concert Band will not disappoint.
        </p>
        <p>Auditions are open to all McMaster University students.</p>
    </div>
</section>
<?php require __DIR__ . "/includes/footer.php"; ?>
