import styles from "@/components/MArker/Marker.module.sass";
import {useDrag} from "react-dnd";
import {ItemType} from "@/constants/draggable";

export enum MarkerPosition {
    Left = "left",
    Right = "right",
}

export type MarkerProps = {
    id: string,
    position: MarkerPosition,
    onSize: Function,
}

export default function Marker({ id, position, onSize }: MarkerProps)
{
    const [{ isDragging }, drag] = useDrag(() => ({
        type: ItemType.MARKER,
        item: () => {
            onSize(id);
            return {taskId: id, direction: position};
        },
        end: () => {
            onSize(null);
        },
        collect: monitor => ({isDragging: monitor.isDragging()}),
    }));

    return (
        <div id={`marker-${id}-${position}`} ref={drag} className={styles.marker} style={{
            gridColumn: `line-${position}-marker`,
            opacity: isDragging ? 0 : 1,
        }}/>
    );
}
