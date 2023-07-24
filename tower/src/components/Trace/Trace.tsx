import styles from './Trace.module.sass'
import {useDrop} from "react-dnd";
import {ConnectDropTarget} from "react-dnd/src/types";
import {ItemTypes} from "@/constants/draggable";
import {put} from "@/utils/card";

export type TraceProps = {
    id: string,
    start: string,
    finish: string,
}

export default function Trace({id, start, finish}: TraceProps)
{
    const [{ isOver }, drop] = useDrop(() => ({
        accept: ItemTypes.CARD,
        drop: ({ cardId }: {cardId: string}) => put(cardId, id),
        collect: monitor => ({
            isOver: monitor.isOver(),
        }),
    })) as [{isOver: boolean}, ConnectDropTarget];

    return <div ref={drop} className={styles.trace} style={{
        gridRow: `line-${id}-start/line-${id}-end`,
        gridColumn: `line-${start}-start/line-${finish ?? start}-end`,
        border: isOver ? '4px solid rgb(181, 12, 15)' : 'none',
    }}/>
}
