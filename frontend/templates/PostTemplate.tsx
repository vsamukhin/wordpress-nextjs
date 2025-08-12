
type PostProps = {
  title?: string;
  content?: string;
};

export default function PostTemplate({ title, content }: PostProps) {
  return (
    <>
      <article>
        {title && <h1>{title}</h1>}
        <div dangerouslySetInnerHTML={{ __html: content || "" }} />
      </article>
    </>
  );
}
