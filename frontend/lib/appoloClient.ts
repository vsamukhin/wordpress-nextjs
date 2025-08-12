import { ApolloClient, InMemoryCache, NormalizedCacheObject } from '@apollo/client';
import { useMemo } from 'react';

let apolloClient: ApolloClient<NormalizedCacheObject> | null = null;

function createApolloClient(): ApolloClient<NormalizedCacheObject> {
  return new ApolloClient({
    ssrMode: typeof window === 'undefined',
    uri: `${process.env.NEXT_PUBLIC_WORDPRESS_URL}/graphql` || "http://localhost:8080/graphql",
    cache: new InMemoryCache(),
  });
}

export function initializeApollo(initialState?: NormalizedCacheObject) {
  const _apolloClient = apolloClient ?? createApolloClient();

  if (initialState) {
    _apolloClient.cache.restore(initialState);
  }

  // на сервере всегда создаём новый клиент
  if (typeof window === 'undefined') return _apolloClient;

  // на клиенте используем один инстанс
  if (!apolloClient) apolloClient = _apolloClient;

  return _apolloClient;
}

export function useApollo(initialState?: NormalizedCacheObject) {
  return useMemo(() => initializeApollo(initialState), [initialState]);
}
