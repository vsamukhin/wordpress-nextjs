// faust.config.js

export default defineFaustConfig({
  wpUrl: process.env.NEXT_PUBLIC_WORDPRESS_URL,
  apiClientSecret: process.env.FAUST_API_SECRET,
});