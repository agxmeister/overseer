'use client'

import useSWR from 'swr'
import Map from "@/components/Map/Map";
import Card from "@/components/Card/Card";

export default function Page() {
    const { data } = useSWR('http://localhost:8080/api/v1/hello', (api: string) => fetch(api).then(res => res.json()));
    const cards = data ?
        data.map((issue: any) => <Card key={issue.key} id={issue.key} date={issue.estimatedStartDate} title={issue.summary}></Card>):
        [];
    return <Map cards={cards}/>
}
