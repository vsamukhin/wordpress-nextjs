export interface PageSeo {
  title: string;
  description: string;
  og_image: string;
  permalink: string;
}

export interface PageContent {
  head: string;
  body: string;
  footer: string;
  bodyClass: string;
  seo: PageSeo;
}

export interface PageProps {
  page: PageContent;
  url?: string;
}