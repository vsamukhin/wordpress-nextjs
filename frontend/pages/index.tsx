import { initializeApollo } from '@/lib/appoloClient';
import { gql } from '@apollo/client';
import { GetStaticProps } from 'next';


interface Page {
  id: string;
  title: string;
  slug: string;
  content: string;
}

interface HomeProps {
  pages: Page[];
}

const GET_PAGES = gql`
  query GetPages {
    pages {
      nodes {
        id
        title
        slug
        content
      }
    }
  }
`;

export default function Home({ pages }: HomeProps) {
  return (
    <main className="p-8">
      <h1 className="text-2xl font-bold mb-4">Страницы из WordPress</h1>
      {pages.map((page) => (
        <div key={page.id} className="mb-6">
          <h2 className="text-xl">{page.title}</h2>
          <div dangerouslySetInnerHTML={{ __html: page.content }} />
        </div>
      ))}
    </main>
  );
}

export const getStaticProps: GetStaticProps<HomeProps> = async () => {
  const apolloClient = initializeApollo();

  const { data } = await apolloClient.query<{ pages: { nodes: Page[] } }>({
    query: GET_PAGES,
  });

  return {
    props: {
      pages: data.pages.nodes,
    },
    revalidate: 60,
  };
};
