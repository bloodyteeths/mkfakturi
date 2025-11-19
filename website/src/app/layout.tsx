import type { Metadata } from "next";
import { Inter } from "next/font/google";
import "./globals.css";

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
  display: "swap",
});

export const metadata: Metadata = {
  title: "Facturino — AI + e‑Faktura platform",
  description: "Most advanced AI‑powered, e‑Faktura‑ready accounting platform for North Macedonia.",
  icons: { icon: "/brand/facturino_logo.png" },
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
