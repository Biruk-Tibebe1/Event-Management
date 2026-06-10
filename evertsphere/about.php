<?php
$page_title = 'About EthioEvents';
$page_has_full_hero = true;
require_once __DIR__ . '/includes/header.php';
?>
<section class="full-bleed bg-animated" style="position:relative;">
  <div id="app-wrapper" class="auth-shell relative z-10 p-4">
    <div class="floating-orb" style="width:300px;height:300px;background:rgba(99,102,241,0.15);top:10%;left:5%;animation-delay:0s;"></div>
    <div class="floating-orb" style="width:200px;height:200px;background:rgba(168,85,247,0.12);bottom:15%;right:10%;animation-delay:2s;"></div>
    <div class="<?php echo $containerClass; ?>">
      <section class="w-full px-6 py-16 md:py-24 max-w-6xl mx-auto">
        <div class="max-w-3xl mx-auto">
    <h1 class="font-heading text-4xl md:text-5xl font-900 text-coffee-800 mb-8">About EthioEvents</h1>
    
    <div class="space-y-8">
      <div class="card-hover rounded-2xl bg-gradient-to-b from-coffee-900/30 to-stone-900/50 border border-coffee-700/20 backdrop-blur-sm p-8">
        <h2 class="font-heading text-2xl font-bold text-coffee-200 mb-4">Our Mission</h2>
        <p class="text-coffee-100/60 leading-relaxed">
          EthioEvents is dedicated to celebrating and preserving Ethiopian cultural heritage through immersive experiences, live events, and secure online ticketing. We connect organizers, guests, and culture enthusiasts with a platform that honors the richness of Ethiopian traditions.
        </p>
      </div>

      <div class="card-hover rounded-2xl bg-gradient-to-b from-coffee-900/30 to-stone-900/50 border border-coffee-700/20 backdrop-blur-sm p-8">
        <h2 class="font-heading text-2xl font-bold text-coffee-200 mb-4">What We Offer</h2>
        <ul class="space-y-3 text-coffee-100/60">
          <li class="flex items-start gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-coffee-400 flex-shrink-0 mt-0.5"></i>
            <span><strong class="text-coffee-200">Event Discovery:</strong> Browse curated Ethiopian cultural events, festivals, and celebrations</span>
          </li>
          <li class="flex items-start gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-coffee-400 flex-shrink-0 mt-0.5"></i>
            <span><strong class="text-coffee-200">Event Access:</strong> Discover and access events with an improved organizer workflow</span>
          </li>
          <li class="flex items-start gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-coffee-400 flex-shrink-0 mt-0.5"></i>
            <span><strong class="text-coffee-200">Organizer Tools:</strong> Comprehensive dashboard for event creators and managers</span>
          </li>
          <li class="flex items-start gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-coffee-400 flex-shrink-0 mt-0.5"></i>
            <span><strong class="text-coffee-200">Cinema Integration:</strong> Curated cinema experiences celebrating Ethiopian cinema</span>
          </li>
          <li class="flex items-start gap-3">
            <i data-lucide="check-circle" class="w-5 h-5 text-coffee-400 flex-shrink-0 mt-0.5"></i>
            <span><strong class="text-coffee-200">Theme Selection:</strong> Personalize your experience with multiple theme options</span>
          </li>
        </ul>
      </div>

      <!-- FAM image moved below Technology & Security -->

      <div class="card-hover rounded-2xl bg-gradient-to-b from-coffee-900/30 to-stone-900/50 border border-coffee-700/20 backdrop-blur-sm p-8">
        <h2 class="font-heading text-2xl font-bold text-coffee-200 mb-4">Technology & Security</h2>
        <p class="text-coffee-100/60 leading-relaxed mb-4">
          Built with modern web technologies including PHP, MySQL, Bootstrap, and Tailwind CSS, EthioEvents prioritizes security, performance, and user experience. Our platform ensures safe transactions and reliable event management.
        </p>
        <p class="text-coffee-100/60 leading-relaxed">
          We respect user privacy and implement industry-standard security practices to protect your personal information and payment details.
        </p>
      </div>

      <div class="card-hover rounded-2xl bg-gradient-to-b from-coffee-900/30 to-stone-900/50 border border-coffee-700/20 backdrop-blur-sm overflow-hidden">
        <img src="<?php echo BASE_URL; ?>/assets/images/FAM.jpg" alt="Like a Family" class="w-full h-56 md:h-64 object-cover block">
        <div class="p-6 bg-coffee-900/10">
          <h2 class="font-heading text-2xl font-bold text-coffee-200 mb-2">Like a Family</h2>
          <p class="text-coffee-100/60">We Did It!</p>
        </div>
      </div>

      <div class="text-center mt-12">
        <a href="<?php echo BASE_URL; ?>" class="inline-block px-8 py-4 gold-gradient text-stone-900 font-semibold rounded-full hover:shadow-lg hover:shadow-coffee-500/30 transition-all">
          <i data-lucide="arrow-left" class="inline w-5 h-5 mr-2"></i>Back to Home
        </a>
      </div>
    </div>
        </div>
      </section>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
