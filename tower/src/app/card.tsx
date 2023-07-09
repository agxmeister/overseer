'use client'

type CardProps = {
    title: string,
}

export default function Card({ title }: CardProps) {
    return (
        <div role={"heading"}>{title}</div>
    )
}
