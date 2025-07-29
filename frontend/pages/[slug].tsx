import { initializeApollo } from "@/lib/appoloClient";
import { gql } from "@apollo/client";
import { GetStaticPaths, GetStaticProps } from "next";

interface Page {
  id: string;
  title: string;
  slug: string;
  content: string;
}

interface PageProps {
  page: Page;
}

const GET_PAGE_BY_SLUG = gql`
  query GetPageBySlug($slug: ID!) {
    page(id: $slug, idType: URI) {
      id
      title
      content
      slug
    }
  }
`;

const GET_ALL_PAGES = gql`
  query GetAllPages {
    pages {
      nodes {
        slug
      }
    }
  }
`;

export default function PageComponent({ page }: PageProps) {
  if (!page) return <p>Страница не найдена</p>;

  return (
    <main className="max-w-3xl mx-auto p-8">
      <h1 className="text-3xl font-bold mb-4">{page.title}</h1>
      <div
        className="prose"
        dangerouslySetInnerHTML={{ __html: page.content }}
      />
    </main>
  );
}

export const getStaticPaths: GetStaticPaths = async () => {
  const apolloClient = initializeApollo();
  const { data } = await apolloClient.query<{ pages: { nodes: { slug: string }[] } }>({
    query: GET_ALL_PAGES,
  });

  const paths = data.pages.nodes.map((p) => ({ params: { slug: p.slug } }));

  return {
    paths,
    fallback: "blocking",
  };
};

export const getStaticProps: GetStaticProps<PageProps> = async ({ params }) => {
  const apolloClient = initializeApollo();

  const { data } = await apolloClient.query<{ page: Page }>({
    query: GET_PAGE_BY_SLUG,
    variables: { slug: params?.slug },
  });

  return {
    props: { page: data.page },
    revalidate: 60,
  };
};
