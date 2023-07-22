import Trace from "@/components/Trace/Trace";
import Card from "@/components/Card/Card";

type TaskProps = {
    id: string,
    trace: Trace,
    card: Card,
}
export default function Task({id, trace, card}: TaskProps) {
    return <>
        {trace}
        {card}
    </>
}
