(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.simpleStars = {
    attach: function (context) {
      once('simple-stars', '.movie-rating-radios', context).forEach(function(container) {
        const labels = container.querySelectorAll('label');

        labels.forEach(function(label) {
          // On hover - highlight this label and previous ones
          label.addEventListener('mouseenter', function() {
            const value = this.getAttribute('for').split('-').pop();
            highlightStars(container, value);
          });

          // On click - same as hover but permanent
          label.addEventListener('click', function() {
            const value = this.getAttribute('for').split('-').pop();
            highlightStars(container, value);
          });
        });

        // Reset on mouse leave
        container.addEventListener('mouseleave', function() {
          const checked = container.querySelector('input:checked');
          if (checked) {
            const value = checked.value;
            highlightStars(container, value);
          } else {
            resetStars(container);
          }
        });

        function highlightStars(container, rating) {
          const labels = container.querySelectorAll('label');
          labels.forEach(function(label) {
            const labelValue = label.getAttribute('for').split('-').pop();
            if (parseInt(labelValue) <= parseInt(rating)) {
              label.style.color = '#ffc107';
            } else {
              label.style.color = '#e9ecef';
            }
          });
        }

        function resetStars(container) {
          const labels = container.querySelectorAll('label');
          labels.forEach(function(label) {
            label.style.color = '#e9ecef';
          });
        }
      });
    }
  };

})(Drupal, once);
