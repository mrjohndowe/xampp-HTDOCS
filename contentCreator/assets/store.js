document.addEventListener('DOMContentLoaded', () => {
  const chips = document.querySelectorAll('[data-filter]');
  const cards = document.querySelectorAll('.product-card[data-category]');
  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      const filter = chip.dataset.filter;
      chips.forEach((item) => item.classList.toggle('is-active', item === chip));
      cards.forEach((card) => {
        card.hidden = filter !== 'all' && card.dataset.category !== filter;
      });
    });
  });
});
