'use client'

import useSWR from 'swr'
import Map from "@/components/Map/Map";
import Card from "@/components/Card/Card";

export default function Page() {
    const { data } = useSWR('http://localhost:8080/api/v1/hello', (api: string) => fetch(api).then(res => res.json()));
    let row = 1;
    const cards = data ?
        data.map((issue: any) => <Card key={issue.key} title={issue.summary} row={`${row++}`} column={issue.estimatedStartDate}></Card>):
        [];
    return <Map cards={cards}/>
}
