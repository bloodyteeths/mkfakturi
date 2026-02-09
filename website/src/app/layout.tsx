import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
  display: "swap",
});

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
        url: "/brand/facturino_logo.png",
        width: 512,
        height: 512,
        alt: "Facturino Logo",
      },
    ],
  },
  twitter: {
    card: "summary_large_image",
    title: "Facturino — AI + e\u2011Faktura platform",
    description:
      "Most advanced AI\u2011powered, e\u2011Faktura\u2011ready accounting platform for North Macedonia.",
    images: ["/brand/facturino_logo.png"],
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

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="mk">
      <body
        className={`${inter.variable} antialiased font - sans`}
      >
        {children}
      </body>
    </html>
  );
}
