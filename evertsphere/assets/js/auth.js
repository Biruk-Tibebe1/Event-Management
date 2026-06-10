(function(){
  'use strict';
  function ready(fn){ if(document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }

  ready(function(){
    // ripple on buttons with id submit-btn
    document.addEventListener('mousedown', function (e) {
      var btn = e.target.closest && e.target.closest('#submit-btn');
      if (!btn) return;
      var ripple = document.createElement('span');
      ripple.className = 'ripple-effect';
      var rect = btn.getBoundingClientRect();
      ripple.style.left = (e.clientX - rect.left) + 'px';
      ripple.style.top = (e.clientY - rect.top) + 'px';
      btn.appendChild(ripple);
      setTimeout(function(){ ripple.remove(); }, 700);
    }, true);

    // small particle utility
    window.createParticles = function(count){
      count = count || 18;
      var container = document.getElementById('app-wrapper');
      if (!container) return;
      var colors = ['#a0522d', '#b8860b', '#cd853f', '#d2a679', '#8b6f47'];
      for(var i=0;i<count;i++){
        var p = document.createElement('div');
        p.className = 'particle';
        p.style.background = colors[Math.floor(Math.random()*colors.length)];
        p.style.left = '50%'; p.style.top = '50%';
        var tx = (Math.random() - 0.5) * 300 + 'px';
        var ty = (Math.random() - 0.5) * 300 + 'px';
        p.style.setProperty('--tx', tx);
        p.style.setProperty('--ty', ty);
        p.style.animation = 'particleFly ' + (0.8 + Math.random()*0.6) + 's ease-out forwards';
        p.style.animationDelay = (Math.random()*0.3) + 's';
        container.appendChild(p);
        (function(el){ setTimeout(function(){ el.remove(); }, 1600); })(p);
      }
    };

    // Toggle password type helper (used by register page if toggle button provided)
    window.togglePassword = function(id, btn){
      var input = document.getElementById(id); if(!input) return;
      var isPass = input.type === 'password';
      input.type = isPass ? 'text' : 'password';
      if(btn) btn.innerHTML = '<i data-lucide="' + (isPass ? 'eye-off' : 'eye') + '" style="width:18px;height:18px;"></i>';
      try{ lucide.createIcons(); } catch(e){}
    };

    try{ lucide.createIcons(); } catch(e){}
  });
})();
