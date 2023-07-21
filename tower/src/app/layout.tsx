import React from "react";
import './globals.css'

export default function RootLayout({
    children,
}: {
    children: React.ReactNode
}) {
    return (
        <html lang="en">
            <body suppressHydrationWarning={true} className={"text-xs font-mono box-content p-4"}>
                {children}
            </body>
        </html>
    )
}
