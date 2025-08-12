
export default function Footer({ data }: { data: string }) {
    return (
    <footer
      dangerouslySetInnerHTML={{ __html: data }}
    />
  );
}
