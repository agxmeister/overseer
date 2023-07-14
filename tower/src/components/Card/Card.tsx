'use client'

import styles from './Card.module.css'

type CardProps = {
    title: string,
}

export default function Card({ title }: CardProps) {
    return (
        <div
            role={"heading"}
            className={styles.container}
        >
            {title}
        </div>
    )
}
