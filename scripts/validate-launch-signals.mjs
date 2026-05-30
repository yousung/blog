const required = ['PUBLIC_SITE_URL'];

const optional = [
  'PUBLIC_ADSENSE_CLIENT',
  'PUBLIC_ADSENSE_SLOT_TOP',
  'PUBLIC_ADSENSE_SLOT_FEED',
  'PUBLIC_ADSENSE_SLOT_ARTICLE',
  'PUBLIC_ADSENSE_SLOT_SIDEBAR',
  'PUBLIC_ADSENSE_SLOT_BOTTOM'
];

const placeholderPatterns = [
  /^https?:\/\/example\.com/i,
  /^G-BLOADEV/i,
  /^ca-pub-0+$/,
  /^placeholder$/i
];

const missing = [];
const invalid = [];
const omitted = [];

function readValue(key) {
  const raw = process.env[key];
  return typeof raw === 'string' ? raw.trim() : '';
}

for (const key of required) {
  const value = readValue(key);

  if (!value) {
    missing.push(key);
    continue;
  }

  if (placeholderPatterns.some((pattern) => pattern.test(value))) {
    invalid.push(`${key}=${value}`);
  }
}

for (const key of optional) {
  const value = readValue(key);

  if (!value) {
    omitted.push(key);
    continue;
  }

  if (placeholderPatterns.some((pattern) => pattern.test(value))) {
    invalid.push(`${key}=${value}`);
  }
}

if (missing.length > 0 || invalid.length > 0) {
  console.error('Launch signal validation failed.');

  if (missing.length > 0) {
    console.error(`Missing required variables: ${missing.join(', ')}`);
  }

  if (invalid.length > 0) {
    console.error(`Placeholder values are not allowed: ${invalid.join(', ')}`);
  }

  process.exit(1);
}

if (omitted.length > 0) {
  console.warn(`Optional launch variables omitted; matching features stay disabled: ${omitted.join(', ')}`);
}

console.log('Launch signal validation passed.');
