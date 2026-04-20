<?php
/*
    Name(s): Jonathan, Marco, Charles, Hanzhi
    Date Created: April 2026
    File Description: Renders the shared site footer and closes the main page layout markup.
*/
?>
    </main>

    <?php $footerRole = currentRole(); ?>
    <footer class="site-footer" aria-label="Site footer">
        <div class="site-footer-inner">
            <section class="footer-brand-block" aria-labelledby="footer-brand-heading">
                <img class="footer-logo" src="<?= e(appUrl("assets/images/logo.jpg")); ?>" alt="ConcertHelper logo">
                <div>
                    <h2 id="footer-brand-heading">McMaster Concert Band</h2>
                    <p>ConcertHelper keeps concerts, parts, recordings, and member access in one place.</p>
                </div>
            </section>

            <nav class="footer-nav" aria-label="Footer navigation">
                <h2>Explore</h2>
                <a href="<?= e(appUrl("index.php")); ?>">Home</a>
                <a href="<?= e(appUrl("members.php")); ?>">Members</a>
                <a href="<?= e(appUrl("concerts.php")); ?>">Concerts</a>
                <?php if ($footerRole !== null): ?>
                    <a href="<?= e(currentDashboardUrl()); ?>">Dashboard</a>
                    <a href="<?= e(appUrl("logout.php")); ?>">Logout</a>
                <?php else: ?>
                    <a href="<?= e(appUrl("login.php")); ?>">Login</a>
                <?php endif; ?>
            </nav>

            <section class="footer-contact" aria-labelledby="footer-contact-heading">
                <h2 id="footer-contact-heading">Contact</h2>
                <p><a href="mailto:professor@mcmaster.ca">resende@mcmaster.ca</a></p>
                <p>McMaster University, Hamilton, Ontario</p>
            </section>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= e((string) date("Y")); ?> McMaster Concert Band</p>
        </div>
    </footer>
    </div>
</body>

</html>
