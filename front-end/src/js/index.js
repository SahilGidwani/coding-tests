const mobileMenu = document.getElementById('mobile-menu');
const menuToggle = document.getElementById('menu-toggle');
const body = document.body;

/* Event listener for toggling the mobile menu */
menuToggle.addEventListener('click', () => {
  menuToggle.classList.toggle('open');
  mobileMenu.classList.toggle('hidden');

  if(menuToggle.classList.contains('open')) {
    body.classList.add('overflow-hidden');
  } else {
    body.classList.remove('overflow-hidden');
  }
});
