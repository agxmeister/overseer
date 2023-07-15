'use client'

import useSWR from 'swr'
import Map from "@/components/Map/Map";
import Card from "@/components/Card/Card";

export default function Page() {
    const { data } = useSWR('http://localhost:8080/api/v1/hello', (api: string) => fetch(api).then(res => res.json()));
    let row = 1;
    let column = 1;
    const cards = data ?
        data.map((title: string) => <Card key={title} title={title} row={row++} column={column++}></Card>):
        [];
    return <Map cards={cards}/>
}
