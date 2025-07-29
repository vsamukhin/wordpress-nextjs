import Link from "next/link";

export default function Header() {
  return (
    <nav className="bg-gray-900 text-white p-4">
      <ul className="flex gap-6">
        <li><Link href="/">Главная</Link></li>
        <li><Link href="/about">О нас</Link></li>
        <li><Link href="/contacts">Контакты</Link></li>
      </ul>
    </nav>
  );
}
