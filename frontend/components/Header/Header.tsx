
export default function Header({ data }: { data: string }) {
  return (
    <header
      dangerouslySetInnerHTML={{ __html: data }}
    />
  );
}
