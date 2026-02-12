import type { Metadata } from "next";
import { Inter, Space_Grotesk } from "next/font/google";
import { headers } from "next/headers";
import "./globals.css";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin", "cyrillic"],
  display: "swap",
});

const spaceGrotesk = Space_Grotesk({
  variable: "--font-space-grotesk",
  subsets: ["latin"],
  display: "swap",
});

const SUPPORTED_LOCALES = ["mk", "sq", "tr", "en"];

const BASE_URL = "https://www.facturino.mk";

export const metadata: Metadata = {
  metadataBase: new URL(BASE_URL),
  title: {
    default: "Facturino — AI + e\u2011Faktura platform",
    template: "%s | Facturino",
  },
  description:
    "Most advanced AI\u2011powered, e\u2011Faktura\u2011ready accounting platform for North Macedonia.",
  icons: { icon: "/brand/facturino_logo.png" },
  openGraph: {
    type: "website",
    siteName: "Facturino",
    title: "Facturino — AI + e\u2011Faktura platform",
    description:
      "Most advanced AI\u2011powered, e\u2011Faktura\u2011ready accounting platform for North Macedonia.",
    url: BASE_URL,
    locale: "mk_MK",
    alternateLocale: ["sq_AL", "tr_TR", "en_US"],
    images: [
      {
        url: "/brand/og-image.jpg",
        width: 1200,
        height: 630,
        alt: "Facturino — AI accounting platform for North Macedonia",
      },
    ],
  },
  twitter: {
    card: "summary_large_image",
    title: "Facturino — AI + e\u2011Faktura platform",
    description:
      "Most advanced AI\u2011powered, e\u2011Faktura\u2011ready accounting platform for North Macedonia.",
    images: ["/brand/og-image.jpg"],
  },
  alternates: {
    canonical: BASE_URL,
    languages: {
      mk: `${BASE_URL}/mk`,
      sq: `${BASE_URL}/sq`,
      tr: `${BASE_URL}/tr`,
      en: `${BASE_URL}/en`,
      "x-default": `${BASE_URL}/mk`,
    },
  },
  robots: {
    index: true,
    follow: true,
  },
};

export default async function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  // Read locale from the x-locale header set by middleware.ts
  const hdrs = await headers();
  const localeHeader = hdrs.get("x-locale") ?? "mk";
  const lang = SUPPORTED_LOCALES.includes(localeHeader) ? localeHeader : "mk";

  return (
    <html lang={lang}>
      <body
        className={`${inter.variable} ${spaceGrotesk.variable} antialiased font-sans`}
      >
        <a
          href="#main-content"
          className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:text-indigo-600 focus:ring-2 focus:ring-indigo-500 focus:rounded-md"
        >
          Skip to content
        </a>
        {children}
      </body>
    </html>
  );
}
