import Header from '@/components/header/Header';
import { useApollo } from '@/lib/appoloClient';
import '@/styles/globals.css';
import { ApolloProvider } from "@apollo/client";
import { FaustProvider } from "@faustwp/core";
import { AppProps } from "next/app";
import { useRouter } from "next/router";


export default function App({ Component, pageProps }: AppProps) {
  const router = useRouter();
  const apolloClient = useApollo(pageProps.initialApolloState);
  
  return (
    <ApolloProvider client={apolloClient}>
      <FaustProvider pageProps={pageProps}>
        <Header />
        <Component {...pageProps} key={router.asPath} />
      </FaustProvider>
    </ApolloProvider>
   
  );
}
