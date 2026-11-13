export const SITE_TITLE = '감성개발자의 Blog';
export const SITE_DESCRIPTION = '11년 차 개발자 감성개발자의 개발 실무·장비 리뷰·육아/일상·재테크 기록.';
export const SITE_URL = import.meta.env.PUBLIC_SITE_URL || 'https://blog.lovizu.com';

// Google AdSense (publisher account shared with tv-blog; site lovizu.com covers blog.lovizu.com)
export const ADSENSE_CLIENT = 'ca-pub-1322828698634218';
// Dedicated ad units created for blog.lovizu.com in the shared AdSense account.
export const AD_SLOTS = {
  postInArticle: '7312059865', // "blog 글 본문 인아티클" — in-article fluid
  postMultiplex: '8032059258' // "blog 글 하단 멀티플렉스" — autorelaxed
} as const;
