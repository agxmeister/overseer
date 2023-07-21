import { render, screen } from '@testing-library/react'
import '@testing-library/jest-dom'
import Card from "./Card";

describe('Card', () => {
    it('renders a title', () => {
        render(<Card id={"T-01"} start={"2023-07-22"} finish={"2023-07-22"} title={"test"}/>)

        const title = screen.getByRole('heading', {
            name: /test/i,
        })

        expect(title).toBeInTheDocument()
    })
})
