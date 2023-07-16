'use client'

import styles from './Card.module.sass'

type CardProps = {
    title: string,
    row: string,
    column: string,
}

export default function Card({ title, row, column }: CardProps) {
    return (
        <div
            role={"heading"}
            className={styles.container}
            style={{
                gridRow: `${row}/${row}`,
                gridColumn: `col-${column}-start/col-${column}-end`,
            }}
        >
            {title}
        </div>
    )
}
