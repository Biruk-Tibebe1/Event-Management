// Basic theme switcher and seat selection helper
document.addEventListener('click', function(e){
  if (e.target.matches('.seat input')){
    var label = e.target.closest('.seat');
    if (e.target.checked) label.classList.add('btn-primary');
    else label.classList.remove('btn-primary');
  }
});

// Theme toggle
document.addEventListener('DOMContentLoaded', function(){
  // Theme modal opens from header; when modal closed, no JS needed.
  // Add small smooth transitions and micro-interactions
  document.documentElement.style.transition = 'background-color 250ms ease, color 250ms ease';
  var cards = document.querySelectorAll('.card');
  cards.forEach(function(c){
    c.style.transition = 'transform 180ms ease, box-shadow 180ms ease';
    c.addEventListener('mouseenter', function(){ c.style.transform = 'translateY(-6px)'; c.style.boxShadow = '0 10px 24px rgba(16,24,40,0.08)'; });
    c.addEventListener('mouseleave', function(){ c.style.transform = ''; c.style.boxShadow = ''; });
  });
});
