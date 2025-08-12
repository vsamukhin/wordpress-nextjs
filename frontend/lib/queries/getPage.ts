import { gql } from "@apollo/client";

export const GET_PAGE = gql`
  query GetPageByUri($uri: ID!) {
    page(id: $uri, idType: URI) {
      id
      title
      elementorContent {
        head
        body
        footer
        bodyClass
        seo {
          title
          description
          og_image
          permalink
        }
      }
    }
  }
`;