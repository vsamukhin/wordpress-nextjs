import Footer from '@/components/Footer/Footer';
import Header from '@/components/Header/Header';
import { useApollo } from '@/lib/appoloClient';
import '@/styles/globals.css';
import { ApolloProvider } from "@apollo/client";
import { FaustProvider } from "@faustwp/core";
import { AppContext, AppProps } from "next/app";
import { useRouter } from "next/router";
import '../faust.config';

type MyAppProps = AppProps & {
  headerData: string;
  footerData: string;
};


export default function App({ Component, pageProps, headerData, footerData }: MyAppProps) {
  const router = useRouter();
  const apolloClient = useApollo(pageProps.initialApolloState);
  
  return (
    <>
        <FaustProvider pageProps={pageProps}>
          <ApolloProvider client={apolloClient}>
            <Header data={headerData} />
            <Component {...pageProps} key={router.asPath} />
            <Footer data={footerData} />
          </ApolloProvider>
        </FaustProvider>
    </>
  );
}

App.getInitialProps = async (appContext: AppContext) => {
  const appProps = await import('next/app').then(mod =>
    mod.default.getInitialProps
      ? mod.default.getInitialProps(appContext)
      : { pageProps: {} }
  );

  const [headerRes, footerRes] = await Promise.all([
    fetch(`${process.env.NEXT_PUBLIC_WORDPRESS_URL}/wp-json/elementor/v1/header`),
    fetch(`${process.env.NEXT_PUBLIC_WORDPRESS_URL}/wp-json/elementor/v1/footer`),
  ]);

  const headerData = await headerRes.json();
  const footerData = await footerRes.json();

  return {
    ...appProps,
    headerData,
    footerData,
  };
};