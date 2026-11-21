const dealers = [
  {
    name: 'CarHop of Pueblo', city: 'Pueblo', address: '601 W 29th St', phone: '719-696-7615',
    image: 'carhop.jpg', logo: 'carhop.png', low: true, financing: 'direct',
    evidence: 'The Pueblo dealership says it finances buyers directly and considers applicants with poor or limited credit.',
    source: 'https://www.carhopofpueblo.com/blogs/auto-financing-in-pueblo-how-to-get-started-with-challenged-credit/',
    catalog: 'https://www.carhopofpueblo.com/used-vehicles/',
    // Each record belongs to one VIN. Only add exteriorFrames for an ordered full
    // rotation, and interiorPanorama for an equirectangular image served with CORS.
    vehicles: [
      {
        name: '2017 Ford Escape SE 4WD', vin: '1FMCU9G95HUA94211',
        listing: 'https://www.carhopofpueblo.com/inventory/used-2017-ford-escape-se-4wd-four-wheel-drive-up-1fmcu9g95hua94211/',
        photos: [
          'https://vehicle-images.carscommerce.inc/97c2-110012911/1FMCU9G95HUA94211/6e99c12cf383a84557007ce406fb79e9.webp',
          'https://vehicle-images.carscommerce.inc/62c6-110012911/1FMCU9G95HUA94211/9e562506c3b6ef86ba5af98acdc14685.webp'
        ]
      }
    ]
  },
  {
    name: 'Discount Motors', city: 'Pueblo', address: '2060 US-50', phone: '719-542-2585',
    image: 'discountmotors.jpg', logo: 'discountmotors.png', low: true, financing: 'lender',
    evidence: 'The dealer advertises financing across credit situations through partner banks and credit unions.',
    source: 'https://www.discountmotorsonline.net/financing',
    catalog: 'https://www.discountmotorsonline.net/cars-for-sale'
  },
  {
    name: 'Car Force Auto Sales', city: 'Pueblo', address: '3606 Morris Ave', phone: '719-334-0337',
    image: 'carforce.jpg', financing: 'unverified',
    evidence: 'Confirm financing options and vehicle availability directly with the lot.'
  },
  {
    name: 'Auto World', city: 'Pueblo', address: '613 E 4th St', phone: '719-242-5467',
    image: 'autoworld.jpg', low: true, financing: 'lender',
    evidence: 'The dealer welcomes all credit histories and its application permits referrals to outside lenders.',
    source: 'https://www.autoworldco.com/financing',
    catalog: 'https://www.autoworldco.com/'
  },
  {
    name: 'DriveTime', city: 'Colorado Springs', image: 'drivetime.jpg', logo: 'drivetime.png',
    low: true, financing: 'unverified',
    evidence: 'The dealer advertises in-house options for a range of credit histories. Confirm who funds the loan and receives payments.',
    source: 'https://www.drivetime.com/articles/colorado-springs/co/in-house-financing',
    catalog: 'https://www.drivetime.com/articles/colorado-springs/co/in-house-financing'
  },
  {
    name: 'Thompson Auto Sales', city: 'Pueblo', address: '525 W 5th St', phone: '719-545-5421',
    low: true, financing: 'lender',
    evidence: 'The dealer refers applicants rebuilding credit to Lendmark Financial Services.',
    source: 'https://pueblousedcars.com/aboutus',
    catalog: 'https://pueblousedcars.com/inventory'
  }
];

const labels = {
  direct: 'Verified buy here, pay here',
  lender: 'Outside lender options',
  unverified: 'Financing details unverified'
};

function append(parent, tag, content, className) {
  const element = document.createElement(tag);
  if (content) element.textContent = content;
  if (className) element.className = className;
  parent.append(element);
  return element;
}

const grid = document.querySelector('#dealerships');
const cards = dealers.map(dealer => {
  const card = append(grid, 'article', '', 'card');
  if (dealer.image) {
    const image = append(append(card, 'div', '', 'photo'), 'img');
    image.src = 'images/' + dealer.image;
    image.alt = '';
    image.loading = 'lazy';
  }
  const body = append(card, 'div', '', 'body');
  const head = append(body, 'div', '', 'head');
  if (dealer.logo) {
    const logo = append(head, 'img', '', 'logo');
    logo.src = 'images/' + dealer.logo;
    logo.alt = '';
  }
  const heading = append(head, 'div');
  append(heading, 'h3', dealer.name);
  append(heading, 'div', [dealer.address, dealer.city].filter(Boolean).join(', ') + ', CO', 'loc');
  append(body, 'div', labels[dealer.financing], 'price');
  append(body, 'p', dealer.evidence, 'note');
  if (dealer.source) {
    const verification = append(body, 'div', '', 'note');
    append(verification, 'a', 'Dealer financing source').href = dealer.source;
    verification.append(' · Checked November 13, 2026');
  }
  if (dealer.catalog) append(body, 'a', 'Browse dealer inventory').href = dealer.catalog;
  for (const vehicle of dealer.vehicles || []) {
    const listing = append(body, 'div', '', 'catalog');
    if (vehicle.photos?.length) {
      const preview = append(listing, 'img');
      preview.src = vehicle.photos[0];
      preview.alt = vehicle.name;
      preview.loading = 'lazy';
      preview.addEventListener('error', () => { preview.hidden = true; });
    }
    append(listing, 'div', vehicle.name);
    append(listing, 'p', 'VIN ' + vehicle.vin + ' · Catalog snapshot, November 13, 2026', 'note');
    const hasSpin = vehicle.exteriorFrames?.length > 1 || vehicle.interiorPanorama;
    append(listing, 'button', hasSpin ? 'View 360°' : 'View catalog photos')
      .addEventListener('click', () => openVehicle(vehicle));
  }
  if (dealer.phone) append(body, 'a', dealer.phone, 'meta').href = 'tel:' + dealer.phone;
  return card;
});

const search = document.querySelector('#dealer-search');
const filter = document.querySelector('#financing-filter');
const count = document.querySelector('#dealer-count');

function filterDealers() {
  const query = search.value.trim().toLowerCase();
  const category = filter.value;
  let matches = 0;
  dealers.forEach((dealer, index) => {
    const location = [dealer.name, dealer.address, dealer.city].join(' ').toLowerCase();
    const financingMatches = category === '' || (category === 'low' ? dealer.low : dealer.financing === category);
    const visible = location.includes(query) && financingMatches;
    cards[index].hidden = !visible;
    if (visible) matches++;
  });
  count.textContent = matches ? matches + (matches === 1 ? ' dealership' : ' dealerships') : 'No dealerships match your filters.';
}

search.addEventListener('input', filterDealers);
filter.addEventListener('change', filterDealers);
filterDealers();

const dialog = document.querySelector('#vehicle-viewer');
const image = document.querySelector('#vehicle-image');
const panorama = document.querySelector('#vehicle-panorama');
const modes = document.querySelector('#media-modes');
const controls = document.querySelector('#photo-controls');
const status = document.querySelector('#media-status');
let viewer;
let frames = [];
let frame = 0;
let dragStart;

function showFrame(index) {
  frame = (index % frames.length + frames.length) % frames.length;
  image.src = frames[frame];
  status.textContent = (frame + 1) + ' of ' + frames.length;
}

function openVehicle(vehicle) {
  const options = [];
  if (vehicle.exteriorFrames?.length > 1) options.push(['Exterior 360°', vehicle.exteriorFrames]);
  if (vehicle.interiorPanorama) options.push(['Interior 360°', vehicle.interiorPanorama]);
  if (vehicle.photos?.length) options.push(['Photos', vehicle.photos]);

  document.querySelector('#vehicle-title').textContent = vehicle.name;
  document.querySelector('#vehicle-source').href = vehicle.listing;
  modes.replaceChildren();
  dialog.showModal();

  function selectMode(label, media) {
    viewer?.destroy();
    viewer = undefined;
    dragStart = undefined;
    status.textContent = '';
    const isPanorama = label === 'Interior 360°';
    image.hidden = isPanorama;
    panorama.hidden = !isPanorama;
    image.classList.toggle('spin', label === 'Exterior 360°');
    controls.hidden = isPanorama || media.length < 2;
    for (const button of modes.children) button.setAttribute('aria-pressed', button.textContent === label);

    if (isPanorama) {
      const reportError = () => {
        status.textContent = 'The interior panorama could not load. Check the dealer listing or choose another view.';
      };
      try {
        viewer = window.pannellum.viewer(panorama, { type: 'equirectangular', panorama: media, autoLoad: true, escapeHTML: true });
        viewer.on('error', reportError);
      } catch {
        reportError();
      }
      return;
    }

    frames = media;
    image.alt = vehicle.name + ' ' + label.toLowerCase();
    showFrame(0);
  }

  for (const [label, media] of options) {
    append(modes, 'button', label).addEventListener('click', () => selectMode(label, media));
  }
  if (options.length) selectMode(...options[0]);
}

document.querySelector('#previous-photo').addEventListener('click', () => showFrame(frame - 1));
document.querySelector('#next-photo').addEventListener('click', () => showFrame(frame + 1));
image.addEventListener('error', () => {
  if (!image.hidden) status.textContent = 'This image could not load. Try another image or open the dealer listing.';
});
image.addEventListener('pointerdown', event => {
  if (!event.isPrimary || event.button !== 0 || !image.classList.contains('spin')) return;
  dragStart = { x: event.clientX, frame };
  image.setPointerCapture(event.pointerId);
});
image.addEventListener('pointermove', event => {
  if (!dragStart || !image.hasPointerCapture(event.pointerId)) return;
  const index = dragStart.frame + Math.trunc((dragStart.x - event.clientX) / 12);
  if ((index % frames.length + frames.length) % frames.length !== frame) showFrame(index);
});
image.addEventListener('lostpointercapture', () => { dragStart = undefined; });
dialog.addEventListener('close', () => {
  viewer?.destroy();
  viewer = undefined;
  dragStart = undefined;
  image.hidden = true;
  image.removeAttribute('src');
});
