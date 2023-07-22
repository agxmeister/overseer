import styles from './Trace.module.sass'

type TraceProps = {
    id: string,
    start: string,
    finish: string,
}
export default function Trace({id, start, finish}: TraceProps)
{
    return <div className={styles.trace} style={{
        gridRow: `line-${id}-start/line-${id}-end`,
        gridColumn: `line-${start}-start/line-${finish ?? start}-end`,
    }}/>
}
