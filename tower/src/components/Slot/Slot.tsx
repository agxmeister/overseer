import styles from './Slot.module.sass'
import {useDrop} from "react-dnd";
import {ConnectDropTarget} from "react-dnd/src/types";
import {ItemType} from "@/constants/draggable";
import {MarkerPosition} from "@/components/Marker/Marker";
import {ApiUrl} from "@/constants/api";

export type SlotProps = {
    id: string,
    position: string,
    onTask: Function,
}

export default function Slot({id, position, onTask}: SlotProps)
{
    const [{ isOver }, drop] = useDrop(() => ({
        accept: ItemType.MARKER,
        drop: ({ taskId, direction }: {taskId: string, direction: string}) => {
            onTask({
                taskId: taskId,
                begin: direction === MarkerPosition.Left ? position : undefined,
                end: direction === MarkerPosition.Right ? position : undefined,
            });
        },
        collect: monitor => ({
            isOver: monitor.isOver(),
        }),
    })) as [{isOver: boolean}, ConnectDropTarget];

    return (
        <div
            ref={drop}
            className={styles.container}
            style={{
                gridRow: `line-${id}-start/line-${id}-end`,
                gridColumn: `line-${position}-start/line-${position}-end`,
                border: isOver ? '4px solid rgb(181, 12, 15)' : 'none',
            }}
        />
    )
}
