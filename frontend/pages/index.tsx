 import ElementorRender from '@/components/ElementorRender/ElementorRender';
import { initializeApollo } from '@/lib/appoloClient';
import { GET_PAGE } from '@/lib/queries';
import { PageContent, PageProps } from '@/types';
import { GetStaticProps, InferGetStaticPropsType } from 'next';
import Head from 'next/head';

export default function Page({ page }: InferGetStaticPropsType<typeof getStaticProps>) { 
  return (
    <>
      <Head>
        {page.seo && (
          <>
            <title>{page.seo.title}</title>
            <meta name="description" content={page.seo.description} />
            <meta property="og:image" content={page.seo.og_image} />
            <meta property="og:url" content={page.seo.permalink} />
          </>
        )}
        <div dangerouslySetInnerHTML={{ __html: page.head }} suppressHydrationWarning />
      </Head>
      <main>
        <ElementorRender body={page.body} footer={page.footer} bodyClass={page.bodyClass} />
      </main>
    </>
  );
}

export const getStaticProps: GetStaticProps<PageProps> = async () => {
  const apolloClient = initializeApollo();

  const { data } = await apolloClient.query({
    query: GET_PAGE,
    variables: { uri: 'home' },
  });

  const elementorContent: PageContent = data.page?.elementorContent || {
    head: '',
    body: '',
    footer: '',
    bodyClass: '',
    seo: { title: '', description: '', og_image: '', permalink: '' },
  };

  return {
    props: {
      page: elementorContent,
    },
    revalidate: 60,
  };
};
