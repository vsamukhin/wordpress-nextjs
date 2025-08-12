WordPress + Next.js с плагином для Elementor и GraphQL
О проекте
Это связка двух приложений:

WordPress с кастомным плагином, который расширяет WPGraphQL и отдаёт контент из Elementor: страницы, шапку, футер и стили.

Next.js — фронтенд-приложение, которое рендерит сайт, запрашивая данные из WordPress GraphQL API.

Отдаёт отдельные области — шапку (header), футер (footer), стили и скрипты, чтобы Next.js мог собрать полный рендер.

Позволяет управлять содержимым сайта в привычном Elementor, а рендерить в Next.js, получая контент через GraphQL.

Запуск
  docker-compose up -d

Доступы после запуска
  WordPress будет доступен на http://localhost:8080
  Next.js — на http://localhost:3000


Для сохранения новых плагинов/страниц :
  docker-compose exec db mysqldump -uwordpress -pwordpress wordpress > db/init.sql
  it add db/init.sql
  git commit -m "Update WordPress DB dump"



WordPress + Next.js with Elementor and GraphQL Plugin
About the project
This is a combination of two applications:

WordPress with a custom plugin that extends WPGraphQL and delivers content from Elementor: pages, header, footer, styles.

Next.js — a frontend application that renders the site by fetching data from the WordPress GraphQL API.

The plugin provides separate areas — header, footer, styles, and scripts — so that Next.js can assemble a complete render.

It allows you to manage site content conveniently in Elementor, while rendering with Next.js by fetching content via GraphQL.

Running the project
  docker-compose up -d

Access after launch
  WordPress will be available at http://localhost:8080
  Next.js will be available at http://localhost:3000

To save new plugins/pages (database dump):
  docker-compose exec db mysqldump -uwordpress -pwordpress wordpress > db/init.sql
  git add db/init.sql
  git commit -m "Update WordPress DB dump"
