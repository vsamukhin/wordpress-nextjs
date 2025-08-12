
type PageProps = {
  title?: string;
  content?: string;
};

export default function PageTemplate({ title, content }: PageProps) {
  return (
    <>
      <main>
        {title && <h1>{title}</h1>}
        <div dangerouslySetInnerHTML={{ __html: content || "" }} />
      </main>
    </>
  );
}
