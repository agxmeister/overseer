import styles from "@/components/MArker/Marker.module.sass";
import {useDrag} from "react-dnd";
import {ItemTypes} from "@/constants/draggable";

export enum MarkerPosition {
    Left = "left",
    Right = "right",
}

export type MarkerProps = {
    id: string
    position: MarkerPosition
    onScale: Function
}

export default function Marker({ id, position, onScale }: MarkerProps)
{
    const [{ isDragging }, drag] = useDrag(() => ({
        type: ItemTypes.MARKER,
        item: () => {
            onScale(id);
            return {taskId: id, direction: position};
        },
        end: () => {
            onScale(null);
        },
        collect: monitor => ({isDragging: monitor.isDragging()}),
    }));

    return (
        <div ref={drag} className={styles.marker} style={{
            gridColumn: `line-${position}-marker`,
            opacity: isDragging ? 0 : 1,
        }}/>
    );
}
