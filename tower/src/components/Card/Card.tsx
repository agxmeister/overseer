'use client'

import styles from './Card.module.sass'

type CardProps = {
    id: string,
    start: string,
    finish: string,
    title: string,
}

export default function Card({ id, start, finish, title }: CardProps) {
    return (
        <div
            role={"heading"}
            className={styles.container}
            style={{
                gridRow: `line-${id}-start/line-${id}-end`,
                gridColumn: `line-${start}-start/line-${finish ?? start}-end`,
            }}
        >
            {title}
        </div>
    )
}
