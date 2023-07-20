'use client'

import styles from './Card.module.sass'

type CardProps = {
    id: string,
    date: string,
    title: string,
}

export default function Card({ id, date, title }: CardProps) {
    return (
        <div
            role={"heading"}
            className={styles.container}
            style={{
                gridRow: `line-${id}-start/line-${id}-end`,
                gridColumn: `line-${date}-start/line-${date}-end`,
            }}
        >
            {title}
        </div>
    )
}
