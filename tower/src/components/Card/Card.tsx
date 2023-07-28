import styles from './Card.module.sass'

export type CardProps = {
    id: string,
    title: string,
}

export default function Card({ id, title }: CardProps)
{
    return (
        <div role={"heading"} className={styles.card}>
            <div className={styles.title}>{title}</div>
        </div>
    )
}
