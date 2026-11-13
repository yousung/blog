export const SITE_TITLE = '감성개발자의 Blog';
export const SITE_DESCRIPTION = '기술과 실무 경험을 기록하는 SEO 기반 개발 블로그.';
export const SITE_URL = import.meta.env.PUBLIC_SITE_URL || 'https://blog.lovizu.com';

// Google AdSense (publisher account shared with tv-blog; site blog.lovizu.com)
export const ADSENSE_CLIENT = 'ca-pub-1322828698634218';
// These slot ids are shared with tv-blog; replace with blog-specific ad units later if needed.
export const AD_SLOTS = {
  postTopDisplay: '6838397081', // responsive display
  postInArticle: '3703987348', // in-article fluid
  postMultiplex: '9736023163' // autorelaxed
} as const;
