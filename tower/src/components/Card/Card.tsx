import styles from './Card.module.sass'
import {format} from "@/utils/date";

export type CardProps = {
    id: string,
    start: string,
    finish: string,
    title: string,
}

export default function Card({ id, start, finish, title }: CardProps) {
    const now = format(new Date());
    const date = now > start ? now < finish ? now : finish ?? start : start;
    return (
        <div
            role={"heading"}
            className={styles.container}
            style={{
                gridRow: `line-${id}-start/line-${id}-end`,
                gridColumn: `line-${date}-start/line-${date}-end`,
            }}
        >
            <div className={styles.title}>{title}</div>
        </div>
    )
}
