import styles from './Slot.module.sass'
import {useDrop} from "react-dnd";
import {ItemTypes} from "@/constants/draggable";
import {put} from "@/utils/card";
import {ConnectDropTarget} from "react-dnd/src/types";

export type SlotProps = {
    id: string,
    position: string,
}

export default function Slot({id, position}: SlotProps)
{
    const [{ isOver }, drop] = useDrop(() => ({
        accept: ItemTypes.CARD,
        drop: ({ cardId }: {cardId: string}) => put(cardId, position),
        collect: monitor => ({
            isOver: monitor.isOver(),
        }),
    })) as [{isOver: boolean}, ConnectDropTarget];

    return <div
        ref={drop}
        className={styles.container}
        style={{
            gridRow: `line-${id}-start/line-${id}-end`,
            gridColumn: `line-${position}-start/line-${position}-end`,
            border: isOver ? '4px solid rgb(181, 12, 15)' : 'none',
        }}
    />
}
