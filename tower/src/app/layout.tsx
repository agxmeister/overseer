import React from "react";
import './globals.css'

export default function RootLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <html lang="en">
            <body suppressHydrationWarning={true} className={"text-sm font-mono box-content p-4"}>
                {children}
            </body>
        </html>
    )
}
